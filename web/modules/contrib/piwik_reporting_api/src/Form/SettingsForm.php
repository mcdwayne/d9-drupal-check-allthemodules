<?php

namespace Drupal\piwik_reporting_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['piwik_reporting_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piwik_reporting_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('piwik_reporting_api.settings');
    $form['token_auth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authentication token'),
      '#description' => $this->t('The Piwik user authentication token. Get it from the Piwik web interface at Administration > Platform > API.'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('token_auth'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('piwik_reporting_api.settings')
      ->set('token_auth', $form_state->getValue('token_auth'))
      ->save();
  }

}
