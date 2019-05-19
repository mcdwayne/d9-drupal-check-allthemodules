<?php

namespace Drupal\webform_mass_email\Form\AdminConfig;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure webform admin settings for mass emails.
 */
class WebformAdminConfigMassEmailForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_admin_config_mass_email_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform_mass_email.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_mass_email.settings');
    $max_execution_time = ini_get('max_execution_time');

    $form['webform_mass_email'] = [
      '#type' => 'details',
      '#title' => $this->t('Webform Mass Email settings'),
      '#open' => TRUE,
    ];
    $form['webform_mass_email']['cron'] = [
      '#type' => 'number',
      '#title' => $this->t('Cron time'),
      '#min' => 1,
      '#default_value' => $config->get('cron'),
      '#description' => $this->t('Sets how much time is being spent per cron run (in seconds).'),
      '#required' => TRUE,
    ];
    if (!empty($max_execution_time)) {
      $form['webform_mass_email']['cron']['#max'] = $max_execution_time;
      $description = $this->t('Cron execution must not exceed the PHP maximum execution time of %max seconds.', [
        '%max' => $max_execution_time,
      ]);
      $form['webform_mass_email']['cron']['#description'] .= ' ' . $description;
    }
    $form['webform_mass_email']['html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow sending as HTML'),
      '#default_value' => $config->get('html'),
      '#description' => $this->t('Check if you would like to have HTML emails sent from the mass email form. Note that this value is checked when the mail system is doing the sending (on cron).'),
    ];
    $form['webform_mass_email']['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log emails'),
      '#default_value' => $config->get('log'),
      '#description' => $this->t('When checked all outgoing mesages are logged in the system log. A logged Email does not guarantee that it is sent or will be delivered. It only indicates that a message is sent to the PHP mail() function.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('webform_mass_email.settings');
    $config->set('cron', intval($form_state->getValue('cron')));
    $config->set('html', boolval($form_state->getValue('html')));
    $config->set('log', boolval($form_state->getValue('log')));
    $config->save();
  }

}
