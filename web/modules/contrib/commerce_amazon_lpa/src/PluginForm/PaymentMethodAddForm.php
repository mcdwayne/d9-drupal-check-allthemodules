<?php

namespace Drupal\commerce_amazon_lpa\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * The payment method add form.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    return [
      '#markup' => Markup::create('@todo this needs to render the wallet widget'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
  }

}
