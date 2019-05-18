<?php

namespace Drupal\bitlink\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for bitlink module.
 */
class BitlinkConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bitlink_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bitlink.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bitlink_config = $this->config('bitlink.settings');

    $form['bitlink_settings'] = [
      '#type' => 'details',
      '#title' => t('Bitlink API settings'),
      '#open' => TRUE,
    ];

    $form['bitlink_settings']['bitlink_settings_file_key'] = [
      '#type' => 'textfield',
      '#title' => t('BitLink Settings File Key ID'),
      '#default_value' => $bitlink_config->get('bitlink_settings_file_key'),
      '#description' => t('ID of the Bitlink Settings file key configured in Key module.'),
      '#required' => TRUE,
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
    $bitlink_config = $this->config('bitlink.settings');

    $bitlink_config
      ->set('bitlink_settings_file_key', $form_state->getValue('bitlink_settings_file_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
