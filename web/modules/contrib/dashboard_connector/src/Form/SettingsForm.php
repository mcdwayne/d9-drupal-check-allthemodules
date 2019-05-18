<?php

namespace Drupal\dashboard_connector\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\dashboard_connector\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dashboard_connector.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dashboard_connector_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Go direct to the config factory so we get a mutable object which takes
    // into account overrides from the settings.php.
    $config = $this->configFactory->get('dashboard_connector.settings');
    $form['#title'] = $this->t('Dashboard Connector Settings');

    $form['config'] = [
      '#type' => 'fieldset',
      '#title' => t('Connector Settings'),
    ];
    $form['config']['enabled'] = [
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Enable to send checks to the Dashboard service.'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('enabled'),
    ];
    $form['config']['base_uri'] = [
      '#title' => $this->t('Base URI'),
      '#type' => 'textfield',
      '#default_value' => $config->get('base_uri'),
      '#description' => $this->t('The base URI for the Dashboard API.'),
    ];
    $form['config']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('client_id'),
    ];
    $form['config']['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('site_id'),
    ];
    $form['config']['env'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('env'),
    ];

    // Authentication.
    $form['auth'] = [
      '#type' => 'fieldset',
      '#title' => t('Authentication settings'),
    ];
    $form['auth']['username'] = [
      '#title' => $this->t('Username'),
      '#type' => 'textfield',
      '#default_value' => $config->get('username'),
      '#description' => $this->t('The Dashboard API username.'),
    ];
    $form['auth']['password'] = [
      '#title' => $this->t('Password'),
      '#type' => 'textfield',
      '#default_value' => $config->get('password'),
      '#description' => $this->t('The Dashboard API password.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('dashboard_connector.settings');
    foreach ($form_state->getValues() as $key => $val) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();
  }

}
