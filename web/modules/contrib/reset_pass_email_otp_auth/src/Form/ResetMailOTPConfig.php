<?php

namespace Drupal\reset_pass_email_otp_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure reset email otp settings.
 */
class ResetMailOTPConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reset_pass_email_otp_auth_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'reset_pass_email_otp_auth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['reset_pass_email_otp_auth_wrong_attempt'] = [
      '#title' => $this->t('Wrong attempt OTP limit'),
      '#type' => 'textfield',
      '#description' => $this->t('How manny user can attempt wrong OTP.'),
      '#default_value' => \Drupal::config('reset_pass_email_otp_auth.settings')
        ->get('reset_pass_email_otp_auth_wrong_attempt'),
    ];

    $form['reset_pass_email_otp_auth_length'] = [
      '#title' => $this->t('Generate OTP character length'),
      '#type' => 'textfield',
      '#description' => $this->t('Generate OTP character length.'),
      '#default_value' => \Drupal::config('reset_pass_email_otp_auth.settings')
        ->get('reset_pass_email_otp_auth_length'),
    ];

    $form['reset_pass_email_otp_auth_mail_subject'] = [
      '#title' => $this->t('Reset Mail OTP mail subject'),
      '#type' => 'textfield',
      '#description' => $this->t('Reset Mail OTP mail subject content.'),
      '#default_value' => \Drupal::config('reset_pass_email_otp_auth.settings')
        ->get('reset_pass_email_otp_auth_mail_subject'),
    ];

    $form['reset_pass_email_otp_auth_mail_body'] = [
      '#title' => $this->t('Reset Mail OTP mail body'),
      '#type' => 'textarea',
      '#description' => $this->t('Reset Mail OTP mail body.'),
      '#default_value' => \Drupal::config('reset_pass_email_otp_auth.settings')
        ->get('reset_pass_email_otp_auth_mail_body'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('reset_pass_email_otp_auth.settings');

    // Save form values.
    $form_values = $form_state->getValues();
    foreach ($form_values as $key => $value) {
      $config->set($key, $form_state->getValue($key))->save();
    }

    parent::submitForm($form, $form_state);
  }

}
