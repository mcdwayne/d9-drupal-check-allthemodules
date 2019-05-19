<?php

namespace Drupal\sceditor\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\FileTransfer\SSH;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Site\Settings;

/**
 * Class FTPAccessForm.
 */
class FTPAccessForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sceditor.ftpaccess',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ftp_access_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->checkFTPStatus();
    $config = $this->config('sceditor.ftpaccess');
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_message"></div>',
      '#prefix' => '<strong>It is recommended to not save password and/or ssh secrets in config. You can also enter the details in settings.php. Just check the <u>`Use settings.php`</u> in the form below.</strong>',
    ];
    $form['use_settings_php'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use settings.php'),
      '#description' => $this->t('If this is checked, it will take the password and key values from settings.php'),
      '#default_value' => $config->get('use_settings_php'),
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('username'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#maxlength' => 32,
      '#size' => 32,
      '#default_value' => $config->get('password'),
      '#suffix' => '<strong>If you do not want to use password, leave the field blank and you can use ssh key from the advanced section instead. (This feature is in development)</strong>',
    ];
    $form['use_ssh_key'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use SSH Key'),
      '#description' => $this->t('If this is checked, authentication will be by ssh key pair.'),
      '#default_value' => $config->get('use_ssh_key'),
    ];
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
    ];
    $form['advanced']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => ($config->get('host')) ? $config->get('host') : 'localhost',
    ];
    $form['advanced']['ssh_private_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private Key Path'),
      '#size' => 64,
      '#default_value' => $config->get('ssh_private_key_path'),
    ];
    $form['advanced']['ssh_public_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public Key Path'),
      '#size' => 64,
      '#default_value' => $config->get('ssh_public_key_path'),
    ];
    $form['advanced']['ssh_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SSH secret (or leave blank for none)'),
      '#size' => 64,
      '#default_value' => $config->get('ssh_secret'),
    ];
    $form['advanced']['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#maxlength' => 16,
      '#size' => 16,
      '#default_value' => ($config->get('port')) ? $config->get('port') : 22,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sceditor.ftpaccess')
      ->set('use_settings_php', $form_state->getValue('use_settings_php'))
      ->set('use_ssh_key', $form_state->getValue('use_ssh_key'))
      ->set('username', $form_state->getValue('username'))
      ->set('ssh_private_key_path', $form_state->getValue('ssh_private_key_path'))
      ->set('ssh_public_key_path', $form_state->getValue('ssh_public_key_path'))
      ->set('ssh_secret', $form_state->getValue('ssh_secret'))
      ->set('password', $form_state->getValue('password'))
      ->set('host', $form_state->getValue('host'))
      ->set('port', $form_state->getValue('port'))
      ->save();

  }

  /**
   * Check id the FTP Access is available.
   */
  public function checkFtpStatus() {
    $config = \Drupal::config('sceditor.ftpaccess'); // @codingStandardsIgnoreLine
    $use_settings_php = $config->get('use_settings_php');
    $use_ssh_key = $config->get('use_ssh_key');
    if ($use_settings_php) {
      $settings = Settings::get('sceditor.settings') ;
      $sftp_user = $settings['username'];
      $sftp_pass = $settings['password'];
      $sftp_private_key = $settings['private_key'];
      $sftp_public_key = $settings['public_key'];
      $sftp_ssh_secret = $settings['ssh_secret'];
    }
    else {
      $sftp_user = $config->get('username');
      $sftp_pass = $config->get('password');
      $sftp_private_key = $config->get('ssh_private_key_path');
      $sftp_public_key = $config->get('ssh_public_key_path');
      $sftp_ssh_secret = $config->get('ssh_secret');
    }
    $sftp_port = $config->get('port');
    $sftp_server = $config->get('host');
    $use_ssh_key = $config->get('use_ssh_key');
    if ($use_ssh_key) {
      $ssh = new SSH(DRUPAL_ROOT, $sftp_user, $sftp_pass, $sftp_server, $sftp_port, $sftp_public_key, $sftp_private_key, $sftp_ssh_secret);
      $ssh->checkConnection();
    }
    else {
      $ssh = new SSH(DRUPAL_ROOT, $sftp_user, $sftp_pass, $sftp_server, $sftp_port);
      $ssh->checkConnection();
    }
    if ($ssh->SSH_CONNECTION_STATUS) {
      drupal_set_message('Server connection successsful.');
    }
    else {
      drupal_set_message('Server connection failed. Please check your settings.', 'error');
    }
    if ($ssh->SSH_PASSWORD_AUTH) {
      drupal_set_message('Connected using username and password.');
    }
    else {
      drupal_set_message('Not connected using username/password.', 'error');
    }
    if ($use_ssh_key && $ssh->SSH_KEY_AUTH) {
      drupal_set_message('Connected using ssh key pair.');
    }
    else {
      drupal_set_message('Not connected using ssh key pair.', 'error');
    }
  }

}
