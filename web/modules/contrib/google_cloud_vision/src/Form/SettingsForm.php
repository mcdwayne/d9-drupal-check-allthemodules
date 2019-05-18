<?php

namespace Drupal\google_cloud_vision\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\google_cloud_vision\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_cloud_vision_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_cloud_vision.settings');

    $form['json_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSON Key Path'),
      '#size' => 120,
      '#maxlength' => 255,
      '#default_value' => $config->get('json_key_path'),
      '#description' => $this->t('Enter the path to the JSON Key File.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('google_cloud_vision.settings');

    $wsdl = $form_state->getValue('json_key_path');

    $config
      ->set('json_key_path', $wsdl);

    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_cloud_vision.settings'];
  }

}
