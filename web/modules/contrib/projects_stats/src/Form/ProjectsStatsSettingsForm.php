<?php

namespace Drupal\projects_stats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Contains \Drupal\projects_stats\Form\ProjectsStatsSettingsForm.
 */

/**
 * Defines a form that configures projects stats module.
 */
class ProjectsStatsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'projects_stats_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['projects_stats.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('projects_stats.settings');

    $form['slack_integration'] = [
      '#type' => 'details',
      '#title' => $this->t('Slack integration'),
      '#open' => TRUE,
    ];

    $form['slack_integration']['send_stats_to_slack'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send downloads count to a Slack channel'),
      '#default_value' => $config->get('send_stats_to_slack'),
    ];

    $form['slack_integration']['machine_names'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Project machine names'),
      '#description' => $this->t('Specify modules/themes/distributions by using their machine names. You can also enter user ID to fetch all projects associated with that user. Separate multiple values by a comma.'),
      '#default_value' => $config->get('machine_names'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="send_stats_to_slack"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['slack_integration']['webhook_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook URL'),
      '#size' => 255,
      '#description' => $this->t('Go to your <a target="_blank" href="https://slack.com/services/new/incoming-webhook">Slack settings</a> and add an integration. After you create an integration you will get a url, which you need to copy to this field.'),
      '#default_value' => $config->get('webhook_url'),
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="send_stats_to_slack"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['slack_integration']['sending_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sending type'),
      '#options' => [
        'drupal_cron' => $this->t('Use Drupal\'s cron'),
        'external_cron' => $this->t('Use external cron'),
      ],
      '#description' => $this->t('If you select Drupal\'s cron, then you will receive message on Slack every time the cron is executed. <a target="_blank" href="/admin/config/system/cron">Configure cron here</a>.<br>If you choose the external cron, then you can set up messaging interval according to your needs.'),
      '#default_value' => empty($config->get('sending_type')) ? 'drupal_cron' : $config->get('sending_type'),
      '#states' => [
        'visible' => [
          ':input[name="send_stats_to_slack"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $host = \Drupal::request()->getHost();
    $form['slack_integration']['external_cron_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('External cron url'),
      '#size' => 255,
      '#description' => $this->t('Call this url to send a message.'),
      '#default_value' => empty($config->get('external_cron_url')) ? $host . '/cron/projects-stats/slack/' . md5(uniqid()) : $config->get('external_cron_url'),
      '#states' => [
        'visible' => [
          ':input[name="send_stats_to_slack"]' => ['checked' => TRUE],
          ':input[name="sending_type"]' => ['value' => 'external_cron'],
        ],
      ],
      '#attributes' => ['readonly' => 'readonly'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('projects_stats.settings');

    $config->set('send_stats_to_slack', $values['send_stats_to_slack']);
    $config->set('machine_names', $values['machine_names']);
    $config->set('webhook_url', $values['webhook_url']);
    $config->set('sending_type', $values['sending_type']);
    $config->set('external_cron_url', $values['external_cron_url']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
