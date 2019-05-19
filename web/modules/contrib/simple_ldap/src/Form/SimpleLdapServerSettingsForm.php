<?php

namespace Drupal\simple_ldap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SimpleLdapServerSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_ldap_server_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_ldap.server',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_ldap.server');

    $form['status'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Connection status'),
    );

    $form['status']['current'] = array(
      '#type' => 'item',
      '#markup' => $this->getBindStatus(),
    );

    $form['server'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('LDAP Server Connection'),
      '#open' => TRUE,
    );

    $form['server']['host'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#required' => TRUE,
      '#default_value' => $config->get('host'),
    );

    $form['server']['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#required' => TRUE,
      '#default_value' => $config->get('port'),
    );

    $form['server']['encryption'] = array(
      '#type' => 'select',
      '#title' => $this->t('Encryption'),
      '#options' => array(
        'none' => $this->t('None'),
        'ssl' => $this->t('SSL'),
        'tls' => $this->t('TLS'),

      ),
      '#default_value' => $config->get('encryption'),
    );

    // For now, we only allow readonly servers.
    $form['server']['readonly'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Read-only'),
      '#default_value' => TRUE,
      '#disabled' => TRUE,
    );

    $form['directory'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Directory Information'),
      '#open' => TRUE,
    );

    $form['directory']['binddn'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Bind DN'),
      '#default_value' => $config->get('binddn'),
      '#description' => $this->t('Leave this blank to bind anonymously'),
    );

    $form['directory']['bindpw'] = array(
      '#type' => 'password',
      '#title' => $this->t('Bind password'),
      '#attributes' => array('value' => array($config->get('bindpw'))),
      '#description' => $this->t('Leave this blank to bind anonymously'),
    );

    $form['directory']['basedn'] = array(
      '#type' => 'textfield',
      '#title' => t('Base DN'),
      '#default_value' => $config->get('basedn'),
      '#description' => t('Leave this blank to attempt to detect the base DN.'),
    );

    $form['directory']['pagesize'] = array(
      '#type' => 'textfield',
      '#title' => t('Search result page size'),
      '#default_value' => $config->get('pagesize'),
      '#description' => $this->t('Leave this blank to disable paged queries.'),
    );

    // Advanced settings.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
    );

    $form['advanced']['opt_referrals'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Follow LDAP referrals'),
      '#default_value' => $config->get('opt_referrals'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('simple_ldap.server');
    $config
      ->set('host', $form_state->getValue('host'))
      ->set('port', $form_state->getValue('port'))
      ->set('encryption', $form_state->getValue('encryption'))
      ->set('readonly', $form_state->getValue('readonly'))
      ->set('binddn', $form_state->getValue('binddn'))
      ->set('bindpw', $form_state->getValue('bindpw'))
      ->set('basedn', $form_state->getValue('basedn'))
      ->set('opt_referrals', $form_state->getValue('opt_referrals'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function that returns a message about the status of the connection.
   */
  protected function getBindStatus() {
    $config = $this->config('simple_ldap.server');
    $host = $config->get('host');
    if (!empty($host)) {
      $server = \Drupal::service('simple_ldap.server');
      $server->connect();
      if ($server->bind()) {
        $status = $this->t("Successfully binded to @host.", array('@host' => $host));
      }
      else {
        $status = $this->t("Could not bind to @host. Please check your settings below.", array('@host' => $host));
      }
    }
    else {
      $status = 'Not connected.';
    }
    return $status;
  }

}
