<?php

namespace Drupal\abuseipdb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Settings extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'abuseipdb_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form Constructor
    $form = parent::buildForm($form, $form_state);
    // Default Settings
    $config = $this->config('abuseipdb.settings');
    // Api Key field
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AbuseIPDB Api Key:'),
      '#default_value' => $config->get('abuseipdb.api_key'),
      '#description' => $this->t('Your AbuseIPDB account which will be linked to the reports delivered to the database')
    ];

    $form['shutdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Emergency shutdown'),
      '#default_value' => $config->get('abuseipdb.shutdown'),
      '#description' => $this->t('Disable checks and reports.')
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('abuseipdb.settings')
      ->set('abuseipdb.api_key', $form_state->getValue('api_key'))
      ->set('abuseipdb.shutdown', $form_state->getValue('shutdown'))
      ->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'abuseipdb.settings',
    ];
  }
}
