<?php

namespace Drupal\commerce_dps_pxpay\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the redirect form builder.
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  protected $payment;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $data = [
      'return' => $form['#return_url'],
      'cancel' => $form['#cancel_url'],
    ];

    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $redirect_url = $payment_gateway_plugin->getRedirect($payment, $data);
    $redirect_method = 'get';

    return $this->buildRedirectForm($form, $form_state, $redirect_url, $data, $redirect_method);
  }

}
