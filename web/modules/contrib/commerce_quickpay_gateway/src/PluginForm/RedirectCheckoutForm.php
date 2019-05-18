<?php

namespace Drupal\commerce_quickpay_gateway\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_quickpay_gateway\CurrencyCalculator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedirectCheckoutForm extends PaymentOffsiteForm implements ContainerInjectionInterface {
  /**
   * @var CurrencyCalculator
   */
  protected $currency_calculator;

  function __construct(CurrencyCalculator $currency_calculator) {
    $this->currency_calculator = $currency_calculator;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_quickpay_gateway.currency_calculator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $data['version'] = 'v10';
    $data['merchant_id'] = $configuration['merchant_id'];
    $data['agreement_id'] = $configuration['agreement_id'];
    $data['order_id'] = $this->createOrderId();
    $data['currency'] = $payment->getAmount()->getCurrencyCode();
    $data['amount'] = $this->currency_calculator->wireAmount($payment->getAmount()->getNumber());
    $data['continueurl'] = $form['#return_url'];
    $data['cancelurl'] = $form['#cancel_url'];
    $data['callbackurl'] = Url::fromRoute('commerce_quickpay_gateway.callback', [], ['absolute' => TRUE])->toString();
    $data['language'] = $configuration['language'];
    $data['autocapture'] = $configuration['autocapture'] ? '1' : '0';
    $data['payment_methods'] = $this->getPaymentMethods();
    $data['autofee'] = $configuration['autofee'] ? '1' : '0';

    // Add payment gateway id and internal order_id as custom variables.
    // Used in the callback method.
    $data['variables[payment_gateway]'] = $payment->getPaymentGatewayId();
    $data['variables[order]'] = $payment->getOrderId();

    $data['checksum'] = $this->getChecksum($data);

    return $this->buildRedirectForm(
      $form,
      $form_state,
      'https://payment.quickpay.net',
      $data,
      PaymentOffsiteForm::REDIRECT_POST
    );
  }

  /**
   * Build the order id taking order prefix into account.
   *
   * @return string
   */
  private function createOrderId() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    $configuration = $this->getConfiguration();
    $order_id = $payment->getOrderId();

    // Ensure that Order number is at least 4 characters otherwise QuickPay will reject the request.
    if (strlen($order_id) < 4) {
      $order_id = substr('000' . $order_id, -4);
    }

    if ($configuration['order_prefix']) {
      $order_id = $configuration['order_prefix'] . $order_id;
    }

    return $order_id;
  }

  /**
   * Get available payment methods.
   *
   * @return string
   */
  private function getPaymentMethods() {
    $configuration = $this->getConfiguration();

    if ($configuration['payment_method'] !== 'selected') {
      return $configuration['payment_method'];
    }

    // Filter out all cards not selected.
    $cards = array_filter($configuration['accepted_cards'], function ($is_selected) {
      return $is_selected;
    }, ARRAY_FILTER_USE_BOTH);

    return implode(',', $cards);
  }

  /**
   * Calculate the md5checksum for the request.
   *
   * @see http://tech.quickpay.net/payments/hosted/#checksum
   *
   * @inheritdoc
   */
  private function getChecksum(array $data) {
    $configuration = $this->getConfiguration();
    ksort($data);
    $base = implode(' ', $data);
    return hash_hmac('sha256', $base, $configuration['api_key']);
  }

  /**
   * @return array
   */
  private function getConfiguration() {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_quickpay_gateway\Plugin\Commerce\PaymentGateway\RedirectCheckout $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    return $payment_gateway_plugin->getConfiguration();
  }
}
