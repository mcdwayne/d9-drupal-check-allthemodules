<?php

/**
 * @file
 * Contains \Drupal\balance_tracker\Form\BalanceTrackerAdjustmentForm.
 */

namespace Drupal\balance_tracker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;

class BalanceTrackerAdjustmentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'balance_tracker_adjustment_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->getRouteMatch()->getRouteName() === 'balance_tracker.user_balance') {
      $user = User::load($this->getRequest()->get('user'));
    }
    else {
      $user = User::load($this->currentUser()->id());
    }
    $form['username'] = array(
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User'),
      '#size' => 20,
      '#default_value' => $user,
      '#maxlength' => 128,
      '#target_type' => 'user',
      '#selection_settings' => ['include_anonymous' => FALSE],
    );
    $form['amount'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#default_value' => '2.00',
      '#size' => 20,
      '#maxlength' => 64,
      '#description' => $this->t('Enter the amount you wish to credit the user here.'),
    );
    $form['message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => 'Admin Adjustment',
      '#size' => 20,
      '#maxlength' => 64,
      '#description' => $this->t('Please enter a log message here.'),
    );
    $form['type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Type'),
      '#default_value' => 'credit',
      '#options' => array(
        'credit' => $this->t('Credit'),
        'debit' => $this->t('Debit'),
      ),
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Adjust'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $account = User::load($form_state->getValue('username'));
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
    if (!is_numeric($form_state->getValue('amount')) || $form_state->getValue('amount') < 0) {
      $form_state->setErrorByName('amount', $this->t('Amount must be a non-negative number.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('username');
    \Drupal::service('balance_tracker.storage')
      ->createEntry($uid, $form_state->getValue('type'), $form_state->getValue('amount'), $form_state->getValue('message'));
    drupal_set_message(t('Transaction Success: Account balance adjusted successfully.'));
    $this->redirect('balance_tracker.user_balance', ['user' => $uid]);
  }

}
