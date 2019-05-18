<?php

namespace Drupal\ms_commerce_saman\PluginForm\OffsiteRedirect;

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
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $gateway_configuration = $payment_gateway_plugin->getConfiguration();
    $merchant_code = $gateway_configuration['merchant_code'];
    
	$redirect_url = 'https://sep.shaparak.ir/payment.aspx';
 
	$redirect_method = 'post';
	$data = [
	  'Amount' => $amount,
	  'ResNum' => $order_id,
	  'MID' => $merchant_code,
	  'RedirectURL' => $redirect,
	];
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }
}
