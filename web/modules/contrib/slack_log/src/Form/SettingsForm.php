<?php

/**
 * @file
 * Contains Drupal\slack\Form\SettingsForm.
 * Configures administrative settings for slack.
 */

namespace Drupal\slack_log\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Class SettingsForm.
 *
 * @package Drupal\slack\Form
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
    return 'slack_log_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['slack_log.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('slack_log.settings');

    $valueOptions = RfcLogLevel::getLevels();

    $form['min_severity_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Minimal Severity Level'),
      '#options' => $valueOptions,
            '#description' => $this->t('What should be the minimal severity level to send a slack message?'),
            '#default_value' => $config->get('min_severity_level'),
      ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('slack_log.settings');
    $config
        ->set('min_severity_level', $form_state->getValue('min_severity_level'))
        ->save();
    parent::submitForm($form, $form_state);
  }

}
