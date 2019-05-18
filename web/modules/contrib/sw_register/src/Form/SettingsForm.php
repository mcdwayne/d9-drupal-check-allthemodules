<?php

namespace Drupal\sw_register\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sw_register.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sw_register.settings');
    $form['service_worker_js_script_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path to your Service Worker script'),
      '#description' => $this->t('Enter a relative path to your service-worker.js (from the site root), e.g. sites/default/files/service-worker.js'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('service_worker_js_script_path'),
    ];
    return parent::buildForm($form, $form_state);
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

    $this->config('sw_register.settings')
      ->set('service_worker_js_script_path', $form_state->getValue('service_worker_js_script_path'))
      ->save();
  }

}
