<?php

namespace Drupal\commerce_gocardless\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for adding GoCardless one off payment methods.
 *
 * Payment methods created via this form do not have a remote ID (mandate) set,
 * and are therefore initially unusable. The remote ID must be set later,
 * by including GoCardlessMandatePane in the checkout flow.
 *
 * @package Drupal\commerce_gocardless\PluginForm
 */
class GoCardlessPaymentMethodAddForm extends PaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // TODO: why does this need to be here?
    // Without this, GoCardlessPaymentGateway::createPaymentMethod is passed
    // NULL as the value of it's $payment_details parameter, causing an
    // error (it's expected to be an array).
    // Adding at least one element to the form stops that happening:
    $form['payment_details']['dummy_value'] = [
      '#type' => 'value',
      '#value' => '1',
    ];

    return $form;
  }

}
