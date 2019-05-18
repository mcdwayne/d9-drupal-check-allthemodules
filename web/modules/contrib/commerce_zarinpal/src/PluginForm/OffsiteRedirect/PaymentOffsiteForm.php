<?php

namespace Drupal\commerce_zarinpal\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    $order_id = $order->id();

    $redirect = Url::fromUri('base:/checkout/' . $order_id . '/payment/return/', ['absolute' => TRUE])
      ->toString();
    $amount = (int) $payment->getAmount()->getNumber();
    if ($payment->getAmount()->getCurrencyCode() == 'IRR') {
      // Converts Iranian Rials to Toman
      $amount = $amount / 10;
    }
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $mode = $gateway_configuration['mode'];
    $merchant_code = $gateway_configuration['merchant_code'];
    // Checks if we are in debug mode.
    if ($mode == 'test') {
      $url = 'https://sandbox.zarinpal.com/pg/services/WebGate/wsdl';
    }
    elseif ($mode == 'live') {
      $url = 'https://zarinpal.com/pg/services/WebGate/wsdl';
    }

    $client = new \SoapClient($url, ['encoding' => 'UTF-8']);

    $result = $client->PaymentRequest(
      [
        'MerchantID' => $merchant_code,
        'Amount' => (int) $amount,
        'Description' => $order->getStore()->label(),
        'CallbackURL' => $redirect,
      ]
    );

    if ($mode == 'test') {
      $redirect_url = 'https://sandbox.zarinpal.com/pg/StartPay/' . $result->Authority;
    }
    elseif ($mode == 'live') {
      $redirect_url = 'https://sandbox.zarinpal.com/pg/StartPay/' . $result->Authority;
    }

    if ($result->Status == 100) {
      $redirect_method = 'post';
      $data = [];
      return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
    }
    else {
      drupal_set_message('Error: ' . $result->Status, 'error');
      $chekout_page = Url::fromUri('base:/checkout/' . $order_id . '/review', ['absolute' => TRUE])
        ->toString();
      return $this->buildRedirectForm($form, $form_state, $chekout_page, [], NULL);
    }
  }
}
