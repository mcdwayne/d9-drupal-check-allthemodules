<?php

namespace Drupal\worldcore\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Worldcore form class.
 */
class PrefillForm extends FormBase {

  /**
   * Internal function.
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'worldcore_prefill_form';
  }

  /**
   * Worldcore form fields.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::config('system.site');

    $query = \Drupal::entityQuery('worldcore_currency')
      ->condition('enabled', 1);
    $currencies_ar = $query->execute();

    $form['currency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Currency'),
      '#options' => $currencies_ar,
      '#default_value' => 'USD',
      '#required' => TRUE,

    ];
    $form['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#default_value' => '',
      '#size' => 10,
      '#maxlength' => 12,
      '#required' => TRUE,
    ];
    $form['memo'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Memo'),
      '#default_value' => $this->t('Payment to @sitename', ['@sitename' => $config->get('name')]),
      '#description' => $this->t("Payment description."),
      '#required' => TRUE,

    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cerate payment'),
    ];

    return $form;
  }

  /**
   * Worldcore form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $error = _validate_payment_form([
      'amount' => $form_state->getValue('amount'),
      'currency' => $form_state->getValue('currency'),
      'memo' => $form_state->getValue('memo'),
    ]);

    if (is_array($error) && count($error) == 2) {
      $form_state->setErrorByName($error[0], $error['msg']);
    }

  }

  /**
   * Worldcore form processing.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $payment = _worldcore_createpayment([
      'amount' => $form_state->getValue('amount'),
      'currency' => $form_state->getValue('currency'),
      'memo' => $form_state->getValue('memo'),
    ]);

    if (is_array($payment) && $payment['pid'] > 0) {

      drupal_set_message($this->t("Please confirm payment details"));

      $form_state->setRedirectUrl(Url::fromUserInput('/worldcore/payment/' . $payment['pid']));

    }

  }

}
