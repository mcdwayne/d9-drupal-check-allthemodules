<?php

namespace Drupal\wallet\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WalletTransactionSettingsForm.
 *
 * @package Drupal\wallet_transaction\Form
 * @ingroup wallet_transaction
 */
class WalletTransactionSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */

  public function getFormId() {
    return 'wallet_transaction_entity_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Empty implementation of the abstract submit class.
  }

  /**
   * Define the form used for WalletTransaction settings.
   *
   * @return array
   *   Form definition array.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['wallet_transaction_settings']['#markup'] = 'Settings form for Wallet Transaction. Manage field settings here.';
    return $form;
  }

}
