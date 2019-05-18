<?php

namespace Drupal\commerce_coinpayments\PluginForm\CoinPaymentsRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CoinPaymentsForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_configuration = $payment_gateway_plugin->getConfiguration();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $payment->getOrder();
    $redirect_method = 'post';

    $data = [
      'cmd' => '_pay',
      'reset' => '1',
      // The store's CoinPayments Merchant ID.
      'merchant' => $payment_configuration['merchant_id'],
      // The path CoinPayments should send the IPN to of our site.
      'ipn_url' => Url::fromRoute('commerce_coinpayments.processipn', [], ['absolute' => TRUE])->toString(),
      // Do not display a comments prompt at CoinPayments.
      'allow_extra' => 0,
      // Do not display a shipping address prompt at CoinPayments.
      'want_shipping' => 0,
      // Return to the review page when payment is canceled.
      'cancel_url' => Url::fromRoute('commerce_payment.checkout.cancel', ['commerce_order' => $order->id(), 'step' => 'cancel'], ['absolute' => TRUE])->toString(),
      // Return to the payment redirect page for processing successful payments.
      'success_url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
      // Set the currency codes.
      'currency' => $payment_configuration['currency_code'],
      // Use the timestamp to generate a unique invoice number.
      'invoice' => $this->commerce_coinpayments_ipn_invoice($order),
      // Define a single item in the cart representing the whole order.
      'amountf' => $order->getTotalPrice()->getNumber(),
      'item_name' => t('Order @order_number at @store', ['@order_number' => $order->id(), '@store' => \Drupal::config('system.site')->get('name')]),
    ];

    $redirect_url = 'https://www.coinpayments.net/index.php';

    foreach ($data as $name => $value) {
      if (isset($value)) {
        $form[$name] = ['#type' => 'hidden', '#value' => $value];
      }
    }

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

  /**
   * Returns a unique invoice number based on the Order ID and timestamp.
   *
   * @param array $ipn_data
   *   Order object
   *
   * @return string
   *   Invoice generated from order object.
   */
  public function commerce_coinpayments_ipn_invoice($order) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    return $order->id() . '-' . \Drupal::time()->getRequestTime();
  }

  /**
   * Returns the IPN URL.
   *
   * @param $method_id
   *   Optionally specify a payment method instance ID to include in the URL.
   */
  public function commerce_coinpayments_ipn_url($instance_id = NULL) {
    $parts = [
      'commerce_coinpayments',
      'ipn',
    ];

    if (!empty($instance_id)) {
      $parts[] = $instance_id;
    }

    return Url::fromUri(implode('/', $parts), ['absolute' => TRUE]);
  }

}
