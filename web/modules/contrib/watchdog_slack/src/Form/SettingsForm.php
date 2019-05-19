<?php

/**
 * @file
 * Contains Drupal\watchdog_slack\Form\SettingsForm.
 * Configures administrative settings for Watchdog to slack.
 */

namespace Drupal\watchdog_slack\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\dblog\Controller\DbLogController;

/**
 * Class SettingsForm.
 *
 * @package Drupal\watchdog_slack\Form
 *
 * @ingroup slack
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'watchdog_slack_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['watchdog_slack.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('watchdog_slack.settings');

    $form['channel'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Slack channel to receive Watchdog notifications.'),
      '#default_value' => $config->get('channel'),
    );

    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Username for notifications'),
      '#description' => $this->t('How do you like to name your Slack bot?'),
      '#default_value' => $config->get('username') ? $config->get('username') : $this->t('Drupal Watchdog'),
    );

    $severity_levels_to_log = RfcLogLevel::getLevels();
    $form['severity_levels_to_log'] = array(
      '#title' => $this->t('Severity levels to log'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 8,
      '#options' => $severity_levels_to_log,
      '#default_value' => $config->get('severity_levels_to_log'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('watchdog_slack.settings');
    $config
      ->set('channel', $form_state->getValue('channel'))
      ->set('username', $form_state->getValue('username'))
      ->set('severity_levels_to_log', $form_state->getValue('severity_levels_to_log'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}