<?php

/**
 * @file
 * Contains \Drupal\strava\Form\StravaConfigurationForm.
 */

namespace Drupal\strava_activities\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class StravaActivitiesConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'strava_activities_configuration_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function getEditableConfigNames() {
    return ['strava_activities_configuration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('strava_activities_configuration.settings');

    $form['cron_sync'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cron synchronization'),
      '#default_value' => $config->get('cron_sync'),
      '#description' => $this->t('Check this if you want to synchronize activity data on each cron run.'),
    ];

    $form['cron_sync_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Cron synchronization time'),
      '#default_value' => $config->get('cron_sync_time') ? $config->get('cron_sync_time') : 86400,
      '#description' => $this->t('Enter the minimum number of seconds between each refresh of the activity synchronization. Setting this to a lower number will result in more API requests.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->config('strava_activities_configuration.settings')
      ->set('cron_sync', $values['cron_sync'])
      ->set('cron_sync_time', $values['cron_sync_time'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
