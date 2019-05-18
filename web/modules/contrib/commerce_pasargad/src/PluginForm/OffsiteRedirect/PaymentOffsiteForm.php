<?php

namespace Drupal\commerce_pasargad\PluginForm\OffsiteRedirect;

use Drupal\commerce_pasargad\Pasargad;
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
    if ($payment->getAmount()->getCurrencyCode() !== 'IRR') {
      // Treats all of the currency codes other than the 'IRR', as Iranian Toman
      // and converts them to Iranian Rials by multiplying by 10.
      $amount = $amount * 10;
    }
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $merchant_code = $gateway_configuration['merchant_code'];
    $terminal_code = $gateway_configuration['terminal_code'];
    $private_key = $gateway_configuration['private_key'];

    $action = '1003';
    $timestamp = time();
    $sign = Pasargad::sign([
      $merchant_code,
      $terminal_code,
      $order_id,
      $order->getCreatedTime(),
      $amount,
      $redirect,
      $action,
      $timestamp,
    ],
      $private_key);

    $redirect_url = 'https://pep.shaparak.ir/gateway.aspx';
    $redirect_method = 'post';
    $data = [
      'invoiceNumber' => $order_id,
      'invoiceDate' => $order->getCreatedTime(),
      'amount' => $amount,
      'terminalCode' => $terminal_code,
      'merchantCode' => $merchant_code,
      'redirectAddress' => $redirect,
      'timeStamp' => $timestamp,
      'action' => $action,
      'sign' => $sign,
    ];
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
