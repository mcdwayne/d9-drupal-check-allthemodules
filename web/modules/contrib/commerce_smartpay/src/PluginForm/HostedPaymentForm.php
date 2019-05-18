<?php

namespace Drupal\commerce_smartpay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class HostedPaymentForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_smartpay\Plugin\Commerce\PaymentGateway\SmartpayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $data = $payment_gateway_plugin->buildTransaction($payment);
    $redirect_url = $payment_gateway_plugin->getUrl();
    
    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, 'POST');
  }


}