<?php

namespace Drupal\abuseipdb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FormCheck extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'abuseipdb_form_check_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form Constructor
    $form = parent::buildForm($form, $form_state);
    // Default Settings
    $config = $this->config('abuseipdb.settings');
    // List of Forms
    $form['forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Form IDs'),
      '#default_value' => $config->get('abuseipdb.forms'),
      '#description' => $this->t('A comma-delimted list of forms which will reject abusive IPs from submitting requests'),
      '#required' => FALSE
    ];
    // Ban IP
    $form['forms_ban_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ban IPs'),
      '#default_value' => $config->get('abuseipdb.forms_ban_ip'),
      '#description' => $this->t('If malicious IP detected during form validation then also ban it.'),
      '#required' => FALSE
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
      ->set('abuseipdb.forms', $form_state->getValue('forms'))
      ->set('abuseipdb.forms_ban_ip', $form_state->getValue('forms_ban_ip'))
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