<?php

namespace Drupal\commerce_paypal\PluginForm\Checkout;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Off-site form for PayPal Checkout.
 *
 * This is provided as a fallback when no "review" step is present in Checkout.
 */
class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_paypal\SmartPaymentButtonsBuilderInterface $builder */
    $builder = \Drupal::service('commerce_paypal.smart_payment_buttons_builder');
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $form['paypal_smart_payment_buttons'] = $builder->build($payment->getOrder(), $payment->getPaymentGateway(), TRUE);
    return $form;
  }

}
