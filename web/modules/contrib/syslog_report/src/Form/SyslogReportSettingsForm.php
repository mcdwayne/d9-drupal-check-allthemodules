<?php

/**
 * @file 
 * Contains \Drupal\syslog_report\Form\SyslogReportSettingsForm
 */

namespace Drupal\syslog_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SyslogReportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'syslog_report_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('syslog_report.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('syslog_report.settings');
    $form['syslog_path'] = [
      '#type' => 'textfield',
      '#title' => t('Enter syslog file path'),
      '#description' => t('E.g: /var/log/syslog'),
      '#required' => TRUE,
      '#default_value' => $config->get('syslog_path'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //Retrieve the configuration
    $this->configFactory->getEditable('syslog_report.settings')
        //set the submitted configuration setting
        ->set('syslog_path', $form_state->getValue('syslog_path'))
        ->save();
  }

}