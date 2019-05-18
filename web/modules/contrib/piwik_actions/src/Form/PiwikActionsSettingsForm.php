<?php

namespace Drupal\piwik_actions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class PiwikActionsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'piwik_actions_admin_configuration';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['piwik_actions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $admin_configurations = $this->config('piwik_actions.settings');
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('Piwik API endpoint URL'),
      '#default_value' => $admin_configurations->get('endpoint') ? $admin_configurations->get('endpoint') : '',
      '#size' => 60,
      '#maxlength' => 60,
      '#required' => TRUE,
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => t('Piwik API token'),
      '#default_value' => $admin_configurations->get('token') ? $admin_configurations->get('token') : '',
      '#size' => 60,
      '#maxlength' => 60,
      '#required' => TRUE,
    ];
    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => t('Piwik API site ID'),
      '#default_value' => $admin_configurations->get('site_id') ? $admin_configurations->get('site_id') : '',
      '#size' => 60,
      '#maxlength' => 60,
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_values = $form_state->getValues();
    $config_fields = [
      'endpoint',
      'token',
      'site_id',
    ];
    $config = $this->config('piwik_actions.settings');
    foreach ($config_fields as $config_field) {
      $config->set($config_field, $config_values[$config_field])
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
