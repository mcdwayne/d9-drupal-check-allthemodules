<?php

namespace Drupal\route_basic_auth\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Configuration form for the route basic auth protection.
 */
class SettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'route_basic_auth_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('HTTP basic authentication credentials'),
    ];

    $form['credentials']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $this->config('route_basic_auth.settings')->get('credentials.username'),
      '#required' => TRUE,
    ];

    $form['credentials']['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#default_value' => $this->config('route_basic_auth.settings')->get('credentials.password'),
      '#required' => TRUE,
    ];

    $form['flood_protection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Flood protection'),
    ];

    $form['flood_protection']['flood_protection_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable flood protection'),
      '#default_value' => $this->config('route_basic_auth.settings')->get('flood_protection_enabled'),
      '#description' => $this->t('The login attempts allowed by a single IP address will be limited to 50 per hour if enabled.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $editableConfig = $this->configFactory()->getEditable('route_basic_auth.settings');

    $editableConfig->set('credentials.username', $form_state->getValue('username'))->save();
    $editableConfig->set('credentials.password', $form_state->getValue('password'))->save();

    $editableConfig->set('flood_protection_enabled', $form_state->getValue('flood_protection_enabled'))->save();
  }

}
