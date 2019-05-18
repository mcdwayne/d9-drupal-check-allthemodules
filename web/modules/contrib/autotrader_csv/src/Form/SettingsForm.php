<?php

namespace Drupal\autotrader_csv\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure autotrader_csv settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['autotrader_csv.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['header'] = [
      '#prefix' => '<h1>',
      '#markup' => $this->t('Autotrader CSV Settings'),
      '#suffix' => '</h1>',
    ];
    $form['description'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('The settings below control how the
                             Autotrader integration works. Please supply a
                             username, password, host, and filename. If you need
                             additional help, please read the README.md.'),
      '#suffix' => '</p>',
    ];
    $form['ftp_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FTP Host'),
      '#default_value' => $this->config('autotrader_csv.settings')->get('ftp_host'),
    ];
    $form['ftp_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FTP Username'),
      '#default_value' => $this->config('autotrader_csv.settings')->get('ftp_username'),
    ];
    $form['ftp_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FTP Password'),
      '#default_value' => $this->config('autotrader_csv.settings')->get('ftp_password'),
    ];
    $form['ftp_filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FTP filename'),
      '#default_value' => $this->config('autotrader_csv.settings')->get('ftp_filename'),
    ];
    $form['seconds_between_uploads'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Seconds between uploads.'),
      '#default_value' => $this->config('autotrader_csv.settings')->get('seconds_between_uploads') ?: (60 * 60 * 24),
      '#description' => $this->t('This amount of time will have to lapse before a cron run will trigger the upload. 86,400 seconds in a 24 hour period.'),
    ];

    // Get list of content types.
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();

    $node_type_options = [];
    foreach ($node_types as $node_type) {
      $node_type_options[$node_type->id()] = $node_type->label();
    }
    $autotrader_csv_node_export_plugins = \Drupal::service('plugin.manager.autotrader_csv_node_export')->getDefinitions();
    $plugin_options = [];
    foreach ($autotrader_csv_node_export_plugins as $id => $autotrader_csv_node_export_plugin) {
      if (in_array($id, array_keys($node_type_options))) {
        $plugin_options[$id] = $node_type_options[$id];
      }
    }
    $form['content_types'] = [
      '#type' => 'checkboxes',
      '#options' => $plugin_options,
      '#title' => $this->t('Choose one or more content types.'),
      '#default_value' => $this->config('autotrader_csv.settings')->get('content_types') ?: [],
      '#description' => $this->t('The content types MUST have an AutotraderCsvNodeExport plugin defined that matches the machine name of the content type selected. Two examples are provided in the autotrader_csv module.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $host = $form_state->getValue('ftp_host');
    $username = $form_state->getValue('ftp_username');
    $password = $form_state->getValue('ftp_password');
    if (!empty($host) && !empty($username) && !empty($password)) {
      $conn_id = ftp_connect($host);
      $login_result = ftp_login($conn_id, $username, $password);
      if ((!$conn_id) || (!$login_result)) {
        $form_state->setErrorByName('ftp_host', $this->t('The server could not be contacted.'));
      }
      else {
        $this->messenger()->addMessage('FTP Connection successful!');
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('autotrader_csv.settings')
      ->set('ftp_host', $form_state->getValue('ftp_host'))
      ->set('ftp_username', $form_state->getValue('ftp_username'))
      ->set('ftp_password', $form_state->getValue('ftp_password'))
      ->set('ftp_filename', $form_state->getValue('ftp_filename'))
      ->set('seconds_between_uploads', $form_state->getValue('seconds_between_uploads'))
      ->set('content_types', $form_state->getValue('content_types'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
