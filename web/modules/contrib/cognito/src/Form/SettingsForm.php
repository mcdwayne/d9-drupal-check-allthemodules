<?php

namespace Drupal\cognito\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Cognito settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cognito.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cognito.settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $messages = $this->config('cognito.settings')->get('messages');

    $form['messages'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Customise Messages'),
    ];

    $form['messages']['password_reset_required'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Password Reset Required'),
      '#size' => 80,
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $messages['password_reset_required'],
      '#description' => $this->t('This message is shown when a user is required to reset their password. This can be forced by the admin or after you have imported users directly into Cognito.'),
    ];

    $form['messages']['account_blocked'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Account Blocked'),
      '#size' => 80,
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $messages['account_blocked'],
      '#description' => $this->t('This message is shown when a users account is blocked either in Cognito or Drupal.'),
    ];

    $form['messages']['registration_complete'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Registration Complete'),
      '#size' => 80,
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $messages['registration_complete'],
      '#description' => $this->t('This message is shown when a user registers but before they have confirmed their registration. E.g. It is shown on the page which shows the confirmation code.'),
    ];

    $form['messages']['registration_confirmed'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Registration Confirmed'),
      '#size' => 80,
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $messages['registration_confirmed'],
      '#description' => $this->t('This message is shown when a user confirms their registration.'),
    ];

    $form['messages']['attempt_confirmation_resend'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Attempting Confirmation Resend'),
      '#size' => 80,
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $messages['attempt_confirmation_resend'],
      '#description' => $this->t('This message is shown when a user attempts to register but their account is already awaiting confirmation. We resend their confirmation code and display this message.'),
    ];

    $form['messages']['user_already_exists_register'] = [
      '#type' => 'textarea',
      '#title' => $this->t('User Already Exists (Registration)'),
      '#size' => 80,
      '#required' => TRUE,
      '#maxlength' => 255,
      '#default_value' => $messages['user_already_exists_register'],
      '#description' => $this->t('This message is shown when a user who already exists attempts to register.'),
    ];

    $form['messages']['click_to_confirm'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message displayed after click to confirm registration'),
      '#size' => 80,
      '#required' => FALSE,
      '#maxlength' => 255,
      '#default_value' => $messages['click_to_confirm'],
      '#description' => $this->t('This message is shown when a user registers and you are making them click a link in their email to confirm their account..'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cognito.settings');

    $messages = $config->get('messages');
    $messages['password_reset_required'] = $form_state->getValue('password_reset_required');
    $messages['account_blocked'] = $form_state->getValue('account_blocked');
    $messages['registration_complete'] = $form_state->getValue('registration_complete');
    $messages['registration_confirmed'] = $form_state->getValue('registration_confirmed');
    $messages['attempt_confirmation_resend'] = $form_state->getValue('attempt_confirmation_resend');
    $messages['user_already_exists_register'] = $form_state->getValue('user_already_exists_register');
    $messages['click_to_confirm'] = $form_state->getValue('click_to_confirm');

    $config
      ->set('messages', $messages)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
