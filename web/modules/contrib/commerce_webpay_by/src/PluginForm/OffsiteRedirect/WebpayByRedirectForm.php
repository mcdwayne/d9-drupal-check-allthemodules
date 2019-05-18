<?php

namespace Drupal\commerce_webpay_by\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_webpay_by\Common\Helper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the class for payment off-site form.
 *
 * Provide a buildConfigurationForm() method which calls buildRedirectForm()
 * with the right parameters.
 */
class WebpayByRedirectForm extends BasePaymentOffsiteForm {

  const PRECISION = 2;

  const DEC_POINT = '.';

  const THOUSANDS_SEP = '';

  const ROUND_MODE = PHP_ROUND_HALF_UP;

  /**
   * Gateway plugin.
   *
   * @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface
   */
  private $paymentGatewayPlugin;

  /**
   * Getting plugin's configuration.
   *
   * @param string $configuration
   *   Configuration name.
   *
   * @return mixed
   *   Configuration value.
   */
  private function getConfiguration($configuration) {
    return $this->paymentGatewayPlugin->getConfiguration()[$configuration] ?? NULL;
  }

  /**
   * Get payment gateway default settings.
   *
   * @param string|null $name
   *   The name of setting.
   *
   * @return array|\Drupal\Core\Config\ImmutableConfig|mixed|null
   *   Default settings.
   */
  private function getDefaultSettings(string $name = NULL) {
    $config = \Drupal::config('commerce_webpay_by.settings');
    return $name ? $config->get("commerce_webpay_by.{$name}") ?? NULL : $config;
  }

  /**
   * Build signature.
   *
   * @param array $data
   *   Signature data.
   *   $data = [
   *     'wsb_seed'        => (string)
   *     'wsb_storeid'     => (string)
   *     'wsb_order_num'   => (string)
   *     'wsb_currency_id' => (string)
   *     'wsb_total'       => (string)
   *     'secret_key'      => (string)
   *   ].
   *
   * @see https://webpay.by/wp-content/uploads/2016/08/WebPay-Developer-Guide-2.1.2_EN.pdf#page=18
   *   The "Order Electronic Signature" section.
   *
   * @return string
   *   The signature.
   */
  private function buildSignature(array $data) {
    $signature = '';
    try {
      $signature .= Helper::fetchArrayValueByKey('wsb_seed', $data);
      $signature .= Helper::fetchArrayValueByKey('wsb_storeid', $data);
      $signature .= Helper::fetchArrayValueByKey('wsb_order_num', $data);
      $signature .= Helper::fetchArrayValueByKey('wsb_test', $data);
      $signature .= Helper::fetchArrayValueByKey('wsb_currency_id', $data);
      $signature .= Helper::fetchArrayValueByKey('wsb_total', $data);
      $signature .= Helper::fetchArrayValueByKey('secret_key', $data);
    }
    catch (\OutOfBoundsException $exception) {
      throw $exception;
    }

    return sha1($signature);
  }

  /**
   * Round price.
   *
   * @param float $number
   *   Price.
   * @param int $precision
   *   Precision.
   * @param int $mode
   *   Round mode.
   *
   * @return float
   *   Rounded price.
   */
  private function round(float $number, $precision = self::PRECISION, $mode = self::ROUND_MODE) {
    return (float) number_format(round($number, $precision, $mode), $precision, self::DEC_POINT, self::THOUSANDS_SEP);
  }

  /**
   * Create URL.
   *
   * @param string $route
   *   The route name.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Url.
   */
  public function url(string $route) {
    return Url::fromRoute($route, [], ['absolute' => TRUE])->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $this->paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();

    $order = $payment->getOrder();
    $sandbox = $this->getConfiguration('mode');
    $redirect_url = $this->getDefaultSettings("{$sandbox}_uri");
    $total_price = 0;
    $shipping_price = 0;
    $data = [];

    $data['*scart'] = NULL;
    $data['wsb_storeid'] = $this->getConfiguration('wsb_storeid');
    $data['wsb_order_num'] = $payment->getOrderId();
    $data['wsb_currency_id'] = $this->getConfiguration('wsb_currency_id');
    $data['wsb_version'] = $this->getDefaultSettings('wsb_version');
    $data['wsb_seed'] = time();
    $data['wsb_notify_url'] = $this->url('commerce_webpay_by.webpay_by_redirect_controller_notify');

    if ($wsb_language_id = $this->getConfiguration('wsb_language_id')) {
      $data['wsb_language_id'] = $wsb_language_id;
    }
    if ($wsb_store = $this->getConfiguration('wsb_store')) {
      $data['wsb_store'] = \Drupal::token()
        ->replace($wsb_store, ['commerce_order' => $order]);
    }
    if ($wsb_return_url = $this->getConfiguration('wsb_return_url')) {
      $data['wsb_return_url'] = \Drupal::token()
        ->replace($wsb_return_url, ['commerce_order' => $order]);
    }
    if ($wsb_cancel_return_url = $this->getConfiguration('wsb_cancel_return_url')) {
      $data['wsb_cancel_return_url'] = \Drupal::token()
        ->replace($wsb_cancel_return_url, ['commerce_order' => $order]);
    }

    /** @var \Drupal\user\Entity\User $user */
    if ($user = \Drupal::currentUser()) {
      $data['wsb_email'] = $user->getEmail();
    }

    $i = 0;
    /** @var \Drupal\commerce_order\Entity\OrderItem $item */
    foreach ($order->getItems() as $item) {
      $price_per_item = $this->round($item->getUnitPrice()->getNumber());
      $quantity = round($item->getQuantity(), 0);
      $data["wsb_invoice_item_name[{$i}]"] = $item->getTitle();
      $data["wsb_invoice_item_quantity[{$i}]"] = $quantity;
      $data["wsb_invoice_item_price[{$i}]"] = $price_per_item;
      $total_price += $price_per_item * $quantity;
      $i++;
    }

    /** @var \Drupal\commerce_order\Adjustment $item */
    foreach ($order->getAdjustments() as $item) {
      if ($item->getType() === 'shipping') {
        $shipping_price += $this->round($item->getAmount()->getNumber());
      }
    }

    $subtotalPrice = $order->getSubtotalPrice()->getNumber();
    $totalPrice = $order->getTotalPrice()->getNumber();
    $discount = $this->round($subtotalPrice - $totalPrice);

    $data['wsb_shipping_price'] = $this->round($shipping_price);
    $data['wsb_discount_price'] = $discount + $data['wsb_shipping_price'];

    $shippingPrice = $total_price + $data['wsb_shipping_price'];
    $discountPrice = $data['wsb_discount_price'];

    $data['wsb_total'] = $this->round($shippingPrice - $discountPrice);
    $data['wsb_test'] = $sandbox;
    $data['secret_key'] = $this->getConfiguration('secret_key');
    $data['wsb_signature'] = $this->buildSignature($data);

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, self::REDIRECT_POST);
  }

}
