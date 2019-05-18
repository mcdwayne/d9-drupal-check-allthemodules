<?php

namespace Drupal\key_auth\Form;

use Drupal\key_auth\KeyAuth;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class KeyAuthSettingsForm.
 */
class KeyAuthSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'key_auth.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'key_auth_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('key_auth.settings');
    $form['auto_generate_keys'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically generate a key when users are created'),
      '#default_value' => $config->get('auto_generate_keys'),
      '#description' => $this->t('This applies only to new users that have access to use key authentication.'),
    ];
    $form['key_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Key length'),
      '#default_value' => $config->get('key_length'),
      '#min' => 8,
      '#max' => 255,
      '#required' => TRUE,
      '#description' => $this->t('Existing keys will not be affected.'),
    ];
    $form['param_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parameter name'),
      '#default_value' => $config->get('param_name'),
      '#required' => TRUE,
      '#description' => $this->t('The name of the parameter used to send the API key via one of the selected detection methods below.'),
    ];
    $form['detection_methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Detection methods'),
      '#default_value' => $config->get('detection_methods'),
      '#required' => TRUE,
      '#options' => [
        KeyAuth::DETECTION_METHOD_HEADER => $this->t('Header'),
        KeyAuth::DETECTION_METHOD_QUERY => $this->t('Query'),
      ],
      '#description' => $this->t('Select one or more methods of detecting the API key.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('key_auth.settings')
      ->set('auto_generate_keys', (bool) $form_state->getValue('auto_generate_keys'))
      ->set('key_length', $form_state->getValue('key_length'))
      ->set('param_name', $form_state->getValue('param_name'))
      ->set('detection_methods', array_values($form_state->getValue('detection_methods')))
      ->save();
  }

}
