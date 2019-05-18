<?php

namespace Drupal\remote_config_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remote_config_sync_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'remote_config_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('remote_config_sync.settings');

    $form['settings'] = [
      '#type' => 'fieldset',
    ];

    $form['settings']['disable_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable confirmation'),
      '#description' => $this->t('Don\'t require confirmation on the configuration sync.'),
      '#default_value' => $config->get('disable_confirmation'),
    ];

    $form['settings']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('remote_config_sync.settings');
    $config->set('disable_confirmation', $form_state->getValue('disable_confirmation'))
      ->save();
  }

}
