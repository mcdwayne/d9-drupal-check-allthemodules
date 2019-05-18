<?php

namespace Drupal\login_alert\Form;

/**
 * @file
 * Contains Drupal\login_alert\Form\LoginAlertConfigurationForm.
 */
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class LoginAlertConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'loginalert.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_alert_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('loginalert.settings');
    $form['login_alert_mail_body'] = [
      '#type' => 'textarea',
      '#default_value' => $config->get('login_alert_mail_body') ? $config->get('login_alert_mail_body') : '',
      '#title' => $this->t('Enter login alert email body'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('loginalert.settings')
      ->set('login_alert_mail_body', $form_state->getValue('login_alert_mail_body'))
      ->save();
    drupal_flush_all_caches();
  }

}
