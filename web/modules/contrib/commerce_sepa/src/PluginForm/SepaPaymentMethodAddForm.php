<?php

namespace Drupal\commerce_sepa\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

class SepaPaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['payment_details'] = $this->buildBankAccountForm($form['payment_details'], $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->validateBankAccountForm($form['payment_details'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->submitBankAccountForm($form['payment_details'], $form_state);

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Builds the bank account form.
   *
   * @param array $element
   *   The target element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   *
   * @return array
   *   The built bank account form.
   */
  protected function buildBankAccountForm(array $element, FormStateInterface $form_state) {
    $element['#attributes']['class'][] = 'bank-account-form';
    $element['iban'] = [
      '#type' => 'textfield',
      '#title' => t('Bank account'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 34,
      '#size' => 34,
      // These keys are used only for theme suggestions.
      // @see system_theme_suggestions_field().
      '#field_name' => 'iban',
      '#field_type' => 'commerce_sepa',
    ];

    return $element;
  }

  /**
   * Validates the bank account form.
   *
   * @param array $element
   *   The bank account form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function validateBankAccountForm(array &$element, FormStateInterface $form_state) {
    $gateway_settings = $this->plugin->getConfiguration();
    $values = $form_state->getValue($element['#parents']);

    // Convert IBAN to human format.
    $values['iban'] = iban_to_human_format($values['iban']);

    if (!verify_iban($values['iban']) ||
      (count($gateway_settings['valid_countries']) &&
        !in_array(iban_get_country_part($values['iban']), $gateway_settings['valid_countries']))) {
      $form_state->setError($element['iban'], t('You have entered an invalid bank account number.'));
    }
    else {
      // Persist the modified values.
      $form_state->setValueForElement($element['iban'], $values['iban']);
    }
  }

  /**
   * Handles the submission of the bank account form.
   *
   * @param array $element
   *   The bank account form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  protected function submitBankAccountForm(array $element, FormStateInterface $form_state) {
    $values = $form_state->getValue($element['#parents']);

    $this->entity->iban = $values['iban'];
  }

}
