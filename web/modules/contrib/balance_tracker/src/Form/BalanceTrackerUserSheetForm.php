<?php

namespace Drupal\balance_tracker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Builds the form to retrieve the balance sheet of a specific user.
 */
class BalanceTrackerUserSheetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'balance_tracker_user_sheet_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['username'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => 'User',
      '#size' => 20,
      '#maxlength' => 128,
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('username');
    $account = User::load($uid);
    // Check if form is blank.
    if ($form_state->isValueEmpty('username')) {
      $form_state->setErrorByName('username', $this->t('You must enter a user name.'));
    }
    // Make sure a valid user account is selected.
    if (!$account) {
      $form_state->setErrorByName('username', $this->t('The user account could not be loaded.'));
    }
    // Make sure user has proper permissions.
    if (!$this->currentUser()->hasPermission('adjust user balances')) {
      $form_state->setErrorByName('', $this->t('You do not have permission to make user balance adjustments.'));
    }
    if ($form_state->getValue('username') !== $this->currentUser()->getAccountName() && !$this->currentUser()->hasPermission('view all balances')) {
      $form_state->setErrorByName('', $this->t('You do not have permission to view other users balance sheets.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('balance_tracker.user_balance', ['user' => $form_state->getValue('username')]);
  }

}
