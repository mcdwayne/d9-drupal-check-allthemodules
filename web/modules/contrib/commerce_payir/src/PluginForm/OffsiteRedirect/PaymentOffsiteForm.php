<?php

namespace Drupal\commerce_payir\PluginForm\OffsiteRedirect;

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
      // Converts Iranian Toman to Rials,
      // because Pay.ir payment service accepts IRR
      $amount = $amount * 10;
    }
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_configuration = $payment_gateway_plugin->getConfiguration();

    $api_key = $gateway_configuration['api_key'];

    $url = 'https://pay.ir/payment/send';

    try {

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api_key&amount=$amount&redirect=$redirect&factorNumber=$order_id");
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $res = curl_exec($ch);
      curl_close($ch);
      $result = json_decode($res);

      if ($result->status == 1) {
        $redirect_method = 'post';
        $data = [];
        $redirect_url = 'https://pay.ir/payment/gateway/' . $result->transId;
        return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
      }
      elseif ($result->status == 0) {
        drupal_set_message('Pay.ir: errCode: ' . $result->errorCode . ' & errMessage: ' . $result->errorMessage, 'error');
      }

    } catch (\Exception $e) {
      drupal_set_message('EError: ' . $e->getMessage(), 'error');
    }

    $checkout_review_page = Url::fromUri('base:/checkout/' . $order_id . '/review', ['absolute' => TRUE])
      ->toString();
    return $this->buildRedirectForm($form, $form_state, $checkout_review_page, [], NULL);

  }
}
