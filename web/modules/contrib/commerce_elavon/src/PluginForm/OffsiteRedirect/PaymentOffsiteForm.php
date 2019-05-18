<?php

namespace Drupal\commerce_elavon\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $module_handler = \Drupal::moduleHandler();

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $configuration = $payment_gateway_plugin->getConfiguration();
    $redirect_method = 'post';

    if ($configuration['mode'] === 'test') {
      $redirect_url = 'https://api.demo.convergepay.com/VirtualMerchantDemo/process.do';
    }
    else {
      $redirect_url = 'https://api.convergepay.com/VirtualMerchant/process.do';
    }

    $order = $payment->getOrder();

    $data = [
      'ssl_merchant_id' => $configuration['merchant_id'],
      'ssl_user_id' => $configuration['user_id'],
      'ssl_pin' => $configuration['pin'],
      'ssl_transaction_type' => 'ccsale',
      'ssl_amount' => $payment->getAmount()->getNumber(),
      'ssl_receipt_link_method' => 'REDG',
      'ssl_receipt_link_url' => $form['#return_url'],
      'ssl_show_form' => 'true',
      'ssl_card_present' => 'N',
      //'ssl_error_url'
      'ssl_receipt_decl_get_url' => $form['#cancel_url'],
      'ssl_invoice_number' => $order->get('order_id')->getString(),
    ];
    if ($configuration['multicurrency']) {
      $data['ssl_transaction_currency'] = $payment->getAmount()->getCurrencyCode();
    }


    // Give custom modules to handle the data before sending over to payment gateway.
    $module_handler->invokeAll('commerce_elavon_offsite_payment', [&$data, $order]);
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
