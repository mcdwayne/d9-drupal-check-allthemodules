<?php

namespace Drupal\external_db_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create form to save database information.
 */
class ExternalDBLoginConfiguration extends ConfigFormBase {

  protected $externalDBLoginService;

  /**
   * {@inheritdoc}
   */
  public function __construct($externalDBLoginService) {
    $this->externalDBLoginService = $externalDBLoginService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('external_db_login.service'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'external_db_login_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['external_db_login.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Getting the configuration value.
    $default_value = $this->config('external_db_login.settings');

    $form['external_db_login_config'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration'),
      '#weight' => 5,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['external_db_login_config']['external_db_login_driver'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Select Driver'),
      '#options' => $this->getDriverOptions(),
      '#default_value' => $default_value->get('external_db_login_driver'),
    ];
    $form['external_db_login_config']['external_db_login_host'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_host'),
      '#required' => TRUE,
      '#title' => $this->t('Hostname'),
    ];
    $form['external_db_login_config']['external_db_login_database'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_database'),
      '#required' => TRUE,
      '#title' => $this->t('Database Name'),
    ];
    $form['external_db_login_config']['external_db_login_username'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_username'),
      '#required' => TRUE,
      '#title' => $this->t('Username'),
    ];
    $form['external_db_login_config']['external_db_login_password'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_password'),
      '#required' => FALSE,
      '#title' => $this->t('Password'),
    ];
    $form['external_db_login_config']['external_db_login_user_table'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_user_table'),
      '#required' => TRUE,
      '#title' => $this->t('User Table Name'),
    ];
    $form['external_db_login_config']['external_db_login_user_email'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_user_email'),
      '#required' => TRUE,
      '#title' => $this->t('Email id field name'),
    ];
    $form['external_db_login_config']['external_db_login_user_password'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_user_password'),
      '#required' => TRUE,
      '#title' => $this->t('Password field name'),
    ];
    $form['external_db_login_config']['external_db_login_user_password_encypt'] = [
      '#type' => 'select',
      '#options' => $this->getPasswordEncryptOptions(),
      '#default_value' => $default_value->get('external_db_login_user_password_encypt'),
      '#required' => TRUE,
      '#title' => $this->t('Password Encription Type'),
    ];
    $form['external_db_login_config']['external_db_login_port'] = [
      '#type' => 'textfield',
      '#maxlength' => 255,
      '#default_value' => $default_value->get('external_db_login_port') ? $default_value->get('external_db_login_port') : '3306' ,
      '#required' => TRUE,
      '#title' => $this->t('Port'),
    ];
    $form['external_db_login_config']['saveconfig'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];
    $form['external_db_login_config_test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test Connection'),
      '#weight' => 6,
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['external_db_login_config_test']['testconnection'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test Connection'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getTriggeringElement()['#parents'][0] === 'testconnection') {
      $db_key = $this->externalDBLoginService->testConnection();
      if ($db_key === 'external_db_login_connection') {
        drupal_set_message($this->t('Connection created'));
      }
      else {
        drupal_set_message($this->t('Connection not created'), 'error');
      }
    }
    if ($form_state->getTriggeringElement()['#parents'][0] === 'saveconfig') {
      $config = $this->config('external_db_login.settings');
      $external_db_login_host = $form_state->getValue('external_db_login_host');
      $external_db_login_username = $form_state->getValue('external_db_login_username');
      $external_db_login_password = $form_state->getValue('external_db_login_password');
      $external_db_login_port = $form_state->getValue('external_db_login_port');
      $external_db_login_database = $form_state->getValue('external_db_login_database');
      $external_db_login_user_table = $form_state->getValue('external_db_login_user_table');
      $external_db_login_driver = $form_state->getValue('external_db_login_driver');
      $external_db_login_user_email = $form_state->getValue('external_db_login_user_email');
      $external_db_login_user_password = $form_state->getValue('external_db_login_user_password');
      $external_db_login_user_password_encypt = $form_state->getValue('external_db_login_user_password_encypt');

      $config->set('external_db_login_host', $external_db_login_host)
        ->set('external_db_login_username', $external_db_login_username)
        ->set('external_db_login_password', $external_db_login_password)
        ->set('external_db_login_port', $external_db_login_port)
        ->set('external_db_login_database', $external_db_login_database)
        ->set('external_db_login_user_table', $external_db_login_user_table)
        ->set('external_db_login_driver', $external_db_login_driver)
        ->set('external_db_login_user_email', $external_db_login_user_email)
        ->set('external_db_login_user_password', $external_db_login_user_password)
        ->set('external_db_login_user_password_encypt', $external_db_login_user_password_encypt)
        ->save();
      drupal_set_message($this->t('Configuration has been saved.'));
    }
  }

  /**
   * Get Driver options.
   */
  protected function getDriverOptions() {
    return array(
      '' => $this->t('-Select-'),
      'mysql' => $this->t('MySQL, MariaDB, Percona Server, or equivalent'),
    );
  }

  /**
   * Get password encrypt policy's options.
   */
  protected function getPasswordEncryptOptions() {
    return array(
      '' => $this->t('-Select-'),
      'md5' => $this->t('MD5'),
      'sha1' => $this->t('SHA1'),
      'hash' => $this->t('HASH'),
      'phpass' => $this->t('PHPass'),
      'sha512' => $this->t('SHA512'),
    );
  }

}
