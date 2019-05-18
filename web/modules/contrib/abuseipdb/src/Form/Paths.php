<?php

namespace Drupal\abuseipdb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class Paths extends ConfigFormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'abuseipdb_paths_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form Constructor
    $form = parent::buildForm($form, $form_state);
    // Default Settings
    $config = $this->config('abuseipdb.settings');

    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths'),
      '#default_value' => $config->get('abuseipdb.paths'),
      '#description' => $this->t('Specify pages where the visitor\'s ip needs to be reported. Enter one path per line. The \'*\' character is a wildcard. A good start is wp-login.php, or/and joomla/*. These visitors will be reported to AbuseIPDB in category \'Web App Attack\'.'),
      '#required' => FALSE
    ];

    $form['paths_ban_ip'] = [
      '#type' => 'checkbox',
      '#title' => 'Ban IP',
      '#default_value' => $config->get('abuseipdb.paths_ban_ip'),
      '#description' => $this->t('Add the IP to Drupal ban list'),
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
      ->set('abuseipdb.paths', $form_state->getValue('paths'))
      ->set('abuseipdb.paths_ban_ip', $form_state->getValue('paths_ban_ip'))
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