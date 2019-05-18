<?php

namespace Drupal\druminate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the Druminate Settings Form.
 *
 * This form is used to get settings for the Druminate Api.
 *
 * @package Drupal\druminate\Form
 */
class DruminateForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['druminate.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'druminate_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('druminate.settings');
    $form['druminate_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => $this->t("An arbitrary value that must be passed when invoking the Luminate Online Client and Server APIs. The value passed by the caller must match the value in the CONVIO_API_KEY site configuration parameter, which is unique for each site."),
      '#required' => TRUE,
    ];
    $form['druminate_secure_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secure URL'),
      '#default_value' => !empty($config->get('secure_url')) ? $config->get('secure_url') : '',
      '#description' => $this->t("In most cases secure2.convio.net or secure3.convio.net matches a clients secure domain. However, in some cases a client will have their own branded secure domain and this will be different. Do not include trailing slash."),
    ];
    $form['druminate_non_secure_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Non-Secure URL'),
      '#default_value' => !empty($config->get('non_secure_url')) ? $config->get('non_secure_url') : '',
      '#description' => $this->t("Do not include trailing slash."),
      '#required' => TRUE,
    ];
    $form['druminate_login_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login Name'),
      '#default_value' => $config->get('login_name'),
      '#description' => $this->t("The user_name of the administrative account that was created for API access."),
      '#required' => TRUE,
    ];
    $form['druminate_login_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login Password'),
      '#default_value' => $config->get('login_password'),
      '#description' => $this->t("The password of the administrative account that was created for API access."),
      '#required' => TRUE,
    ];

    $form['druminate_test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test Mode'),
      '#default_value' => $config->get('test_mode'),
      '#description' => $this->t("Add the df_preview parameter to all donation submissions."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('druminate.settings');
    $config
      ->set('api_key', $form_state->getValue('druminate_api_key'))
      ->set('host', $form_state->getValue('druminate_host'))
      ->set('test_mode', $form_state->getValue('druminate_test_mode'))
      ->set('secure_url', $form_state->getValue('druminate_secure_url'))
      ->set('non_secure_url', $form_state->getValue('druminate_non_secure_url'))
      ->set('short_name', $form_state->getValue('druminate_short_name'))
      ->set('login_name', $form_state->getValue('druminate_login_name'))
      ->set('login_password', $form_state->getValue('druminate_login_password'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
