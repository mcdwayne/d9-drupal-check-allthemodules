<?php

namespace Drupal\janrain_connect_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * JanrainConnectUiConfigAuthForm.
 */
class JanrainConnectUiConfigAuthForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_ui_config_auth';
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
    $form['config_auth'] = [
      '#type' => 'details',
      '#title' => 'Config Auth',
      '#open' => TRUE,
    ];

    $form['config_auth']['config_auth_check_email_verified'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('If checked only users with email verified in Janrain can log in website'),
      '#default_value' => $this
        ->config('janrain_connect.settings')
        ->get('config_auth_check_email_verified'),
    ];

    $form['config_auth_email_verification'] = [
      '#type' => 'details',
      '#title' => 'Email Verification Redirects',
      '#open' => TRUE,
    ];

    $form['config_auth_email_verification']['config_auth_email_verification_redirect_success'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email verification success path'),
      '#default_value' => $this
        ->config('janrain_connect.settings')
        ->get('config_auth_email_verification_redirect_success'),
    ];

    $form['config_auth_email_verification']['config_auth_email_verification_redirect_fail'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email verification fail path'),
      '#default_value' => $this
        ->config('janrain_connect.settings')
        ->get('config_auth_email_verification_redirect_fail'),
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
        'config_auth_check_email_verified',
        $form_state->getValue('config_auth_check_email_verified')
      )
      ->set(
        'config_auth_email_verification_redirect_fail',
        $form_state->getValue('config_auth_email_verification_redirect_fail')
      )
      ->set(
        'config_auth_email_verification_redirect_success',
        $form_state->getValue('config_auth_email_verification_redirect_success')
      )
      ->save();
  }

}
