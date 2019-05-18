<?php

namespace Drupal\drift\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DriftSettingsForm
 *
 * @package Drupal\drift\Form
 */
class DriftSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drift_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'drift.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drift.settings');
    $elements = [ 1 => 'Enabled', 0 => 'Disabled'];
    $status = $config->get('status');

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Drift is:'),
      '#default_value' => $status,
      '#options' => $elements
    ];

    $form['identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drift Load:'),
      '#description' => $this->t('See how to find out this value: https://youtu.be/v28cAnaEEcI'),
      '#default_value' => $config->get('identifier'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the configuration
    $this->configFactory->getEditable('drift.settings')
      // Set the submitted configuration setting
      ->set('status', $form_state->getValue('status'))
      // You can set multiple configurations at once by making
      // multiple calls to set()
      ->set('identifier', $form_state->getValue('identifier'))
      ->save();

    //Flushing JS cache to avoid caching of old identifier.
    \Drupal::service('asset.js.collection_optimizer')
      ->deleteAll();
    
    _drupal_flush_css_js();

    parent::submitForm($form, $form_state);
  }

}