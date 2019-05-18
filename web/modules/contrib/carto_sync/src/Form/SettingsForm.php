<?php

namespace Drupal\carto_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\carto_sync\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'carto_sync_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'carto_sync.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('carto_sync.settings');

    $form['carto_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CARTO ID'),
      '#description' => $this->t('Enter your CARTO user ID.'),
      '#default_value' => $config->get('carto_id'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['carto_api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('CARTO API Key'),
      '#description' => $this->t('Enter your CARTO API Key. Keep it secret.'),
      '#default_value' => $config->get('carto_api_key'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 64,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('carto_sync.settings')
      ->set('carto_id', $form_state->getValue('carto_id'))
      ->set('carto_api_key', $form_state->getValue('carto_api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
