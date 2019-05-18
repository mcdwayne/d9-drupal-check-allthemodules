<?php

/**
 * @file
 * Contains \Drupal\monitoring\Form\MonitoringSettingsForm.
 */

namespace Drupal\monitoring\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for general Monitoring configuration.
 */
class MonitoringSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'monitoring_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['monitoring.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('monitoring.settings');

    $options = [
      'all' => $this->t('Log all events'),
      'on_request' => $this->t('Log only on request or on status change'),
      'none' => $this->t('No logging'),
    ];
    $form['sensor_call_logging'] = array(
      '#title' => $this->t('Monitoring event logging'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $config->get('sensor_call_logging'),
      '#description' => $this->t('Control local logging of sensor call results.'),
    );
    $form['cron_run_sensors'] = [
      '#title' => $this->t('Run sensors during cron runs'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('cron_run_sensors'),
      '#description' => $this->t('In this mode, monitoring will not be able to detect if cron is running. It is recommended to fetch sensor results with drush or through REST requests for a more reliable setup.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('monitoring.settings')
      ->set('sensor_call_logging', $form_state->getValue('sensor_call_logging'))
      ->set('cron_run_sensors', $form_state->getValue('cron_run_sensors'))
      ->save();
  }

}
