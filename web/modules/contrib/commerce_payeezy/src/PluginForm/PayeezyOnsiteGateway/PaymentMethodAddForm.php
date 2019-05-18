<?php

namespace Drupal\commerce_payeezy\PluginForm\PayeezyOnsiteGateway;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Payment form for Onsite Gateway method.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildCreditCardForm($element, $form_state);
    $element['number']['#placeholder'] = 'Credit card number';

    $element['card_owner_name'] = [
      '#type' => 'textfield',
      '#title' => t('Card owner name'),
      '#size' => 20,
      '#attributes' => ['autocomplete' => 'off'],
      '#weight' => -1,
      '#required' => TRUE,
    ];

    return $element;
  }

}
