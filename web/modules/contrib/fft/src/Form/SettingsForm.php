<?php

namespace Drupal\fft\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fft.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fft_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['fft_storage_dir'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Formatter template directory'),
      '#default_value' => $this->config('fft.settings')->get('fft_storage_dir'),
      '#description' => $this->t('Configure directory storage field formatter template. Each directory per line.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fft.settings')
      ->set('fft_storage_dir', $form_state->getValue('fft_storage_dir'))
      ->save();
  }

}
