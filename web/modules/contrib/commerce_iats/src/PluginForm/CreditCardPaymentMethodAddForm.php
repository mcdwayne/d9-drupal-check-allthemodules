<?php

namespace Drupal\commerce_iats\PluginForm;

use Drupal\commerce_payment\CreditCard;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CreditCardPaymentMethodAddForm.
 */
class CreditCardPaymentMethodAddForm extends PaymentMethodAddFormBase {

  /**
   * {@inheritdoc}
   */
  protected function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // If not hosted, just do the standard credit card form.
    if (!$this->isHosted()) {
      return parent::buildCreditCardForm($element, $form_state);
    }

    return $this->buildHostedForm($element, 'credit_card');
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    if ($this->isHosted()) {
      $this->captureCryptogram($element, $form_state);
      return;
    }

    // Non-hosted, determine credit card type.
    $values = $form_state->getValue($element['#parents']);
    $card_type = CreditCard::detectType($values['number']);
    if (!$card_type) {
      $form_state->setError($element['number'], t('You have entered a credit card number of an unsupported card type.'));
      return;
    }

    // iATS handles dinersclub as amex.
    $card_type = $card_type->getId();
    if ($card_type == 'dinersclub') {
      $card_type = 'amex';
    }

    // Persist the detected card type.
    $form_state->setValueForElement($element['type'], $card_type);
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // No op.
  }

}
