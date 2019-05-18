<?php

namespace Drupal\commerce_bitpayir\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_bitpayir\BitpayGateway;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();
    $order_id = $order->id();

    $redirect = Url::fromUri('base:/checkout/'.$order_id.'/payment/return/', ['absolute' => TRUE])->toString();
    // Bitpay Accepts just integer values.
    $amount = (int) $payment->getAmount()->getNumber();
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $mode = $gateway_configuration['mode'];
    $api = $gateway_configuration['api_code'];
    // Checks if we are in debug mode.
    if($mode == 'test') {
      $url = 'https://bitpay.ir/payment-test/gateway-send';
    }
  elseif ($mode == 'live') {
      $url = 'https://bitpay.ir/payment/gateway-send';
    }

    $id_get = BitpayGateway::send($url, $api, $amount, $redirect);
    if ($mode == 'test') {
      $redirect_url = 'https://bitpay.ir/payment-test/gateway-' . $id_get;
    } elseif ($mode == 'live') {
      $redirect_url = 'https://bitpay.ir/payment/gateway-' . $id_get;
    }
    // All of states which in we call 'https://bitpay.ir/payment/gateway-send
    // via curl fuction and will returned.
    // (or 'https://bitpay.ir/payment-test/gateway-send in debug mode)
    $bitpay_result_messages = [
      -1 => t('Bitpay: api code is not compatible.'),
      -2 => t('Bitpay: order amount is not numeric or is below 1000 rial.'),
      -3 => t('Bitpay: redirect value is null.'),
      -4 => t('Bitpay: account associated with api is not found or is in review state.'),
    ];
    if ($id_get < 0) {
      if ($id_get > -5) {
        \Drupal::logger('commerce_bitpayir')->error($bitpay_result_messages[$id_get]);
        drupal_set_message($bitpay_result_messages[$id_get], 'error');
      }
      else {
        $bitpay_result_messages[-5] = t('Bitpay: unknown error: @error', array('@error' => $id_get));
        \Drupal::logger('commerce_bitpayir')->error($bitpay_result_messages[-5]);
        drupal_set_message($bitpay_result_messages[-5], 'error', FALSE);
      }
      $chekout_page = Url::fromUri('base:/checkout/'.$order_id.'/review', ['absolute' => TRUE])->toString();
      return $this->buildRedirectForm($form, $form_state,$chekout_page, [], NULL);
    }
    else {
      $redirect_method = 'post';
      $data = [];
      return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
    }
  }
}
