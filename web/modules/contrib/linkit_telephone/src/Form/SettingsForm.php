<?php

namespace Drupal\linkit_telephone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linkit_telephone_settings';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   * An array of configuration object names that are editable if called in
   * conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['linkit_telephone.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linkit_telephone.settings');
    $form['default_region_code'] = [
      '#type' => 'textfield',
      '#title' => t('Default region code'),
      '#default_value' => $config->get('default_region_code') ? $config->get('default_region_code') : '',
      '#size' => 5,
      '#maxlength' => 5,
      '#description' => t("Default region code for non-international numbers."),
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
      'default_region_code',
    ];
    $config = $this->config('linkit_telephone.settings');
    foreach ($config_fields as $config_field) {
      $config->set($config_field, $config_values[$config_field])
          ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
