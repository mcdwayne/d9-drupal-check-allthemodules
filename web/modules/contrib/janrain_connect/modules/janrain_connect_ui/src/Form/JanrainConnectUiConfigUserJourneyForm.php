<?php

namespace Drupal\janrain_connect_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * JanrainConnectUiConfigUserJourneyForm.
 */
class JanrainConnectUiConfigUserJourneyForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_ui_config_user_journey_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['config_forgot_password_verification'] = [
      '#type' => 'details',
      '#title' => 'Forgot Password Redirects',
      '#open' => TRUE,
    ];

    $form['config_forgot_password_verification']['config_forgot_password_verification_redirect_success'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Forgot password success path'),
      '#default_value' => $this
        ->config('janrain_connect.settings')
        ->get('config_forgot_password_verification_redirect_success'),
    ];

    $form['config_forgot_password_verification']['config_forgot_password_verification_redirect_fail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Forgot password fail path'),
      '#default_value' => $this
        ->config('janrain_connect.settings')
        ->get('config_forgot_password_verification_redirect_fail'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('janrain_connect.settings')
      ->set(
        'config_forgot_password_verification_redirect_fail',
        $form_state->getValue('config_forgot_password_verification_redirect_fail')
      )
      ->set(
        'config_forgot_password_verification_redirect_success',
        $form_state->getValue('config_forgot_password_verification_redirect_success')
      )
      ->save();
  }

}
