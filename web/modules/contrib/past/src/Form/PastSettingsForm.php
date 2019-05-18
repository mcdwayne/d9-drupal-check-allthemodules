<?php

namespace Drupal\past\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslationWrapper;

/**
 * Displays the pants settings form.
 */
class PastSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['past.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('past.settings');
    $date_formatter = \Drupal::service('date.formatter');

    // Options for events_expire
    $expire_options = [86400, 604800, 604800 * 4];

    $form['events_expire'] = [
      '#type'  => 'select',
      '#title' => t('Log expiration interval'),
      '#description' => t('Specify the time period to be used expiring past events.'),
      '#default_value' => $config->get('events_expire'),
      '#options' => array_map([$date_formatter, 'formatInterval'], array_combine($expire_options, $expire_options)),
      '#empty_option' => '- None -',
    ];
    $form['shutdown_handling'] = [
      '#type' => 'checkbox',
      '#title' => t('Register PHP shutdown error handler'),
      '#default_value' => $config->get('shutdown_handling'),
      '#description' => t('When enabled, Past will register a shutdown handler that logs previously uncaught PHP errors.'),
    ];
    $form['exception_handling'] = [
      '#type' => 'checkbox',
      '#title' => t('Register PHP exception handler'),
      '#default_value' => $config->get('exception_handling'),
      '#description' => t('When enabled, Past will log every exception via its PHP exception handler.'),
    ];
    $form['log_session_id'] = [
      '#type' => 'checkbox',
      '#title' => t('Log the session id'),
      '#default_value' => $config->get('log_session_id'),
      '#description' => t("When enabled, Past will log the user's session id and entries can be traced by session id."),
    ];
    $form['watchdog'] = [
      '#type' => 'fieldset',
      '#title' => t('Watchdog logging'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['watchdog']['log_watchdog'] = [
      '#type' => 'checkbox',
      '#title' => t('Log watchdog to past event log'),
      '#default_value' => $config->get('log_watchdog'),
      '#description' => t('When enabled, Past will take watchdog log entries. <em>To avoid redundancy, you can turn off the database logging module.</em>'),
    ];

    $included = [];
    $levels = [];
    // Options for severity_threshold. Special label for RfcLogLevel::DEBUG.
    $severity_options = [];
    // Avoid special case RfcLogLevel::CRITICAL int (0) with a prefix.
    foreach (RfcLogLevel::getLevels() as $key => $value) {
      if (in_array($key, $config->get('backtrace_include'))) {
        $included[$key] = 'severity_' . $key;
      }
      $levels['severity_' . $key] = $value;
      $severity_options[$key] = ($key == RfcLogLevel::DEBUG) ? t('@value (no threshold)', ['@value' => $value]) : $value;
    }
    $form['watchdog']['backtrace_include'] = [
      '#type' => 'checkboxes',
      '#default_value' => $included,
      '#options' => $levels,
      '#title' => t('Watchdog severity levels from writing backtraces'),
      '#description' => t('A backtrace is logged for all severities that are checked.'),
      '#states' => ['visible' => ['input[name="log_watchdog"]' => ['checked' => TRUE]]],
    ];

    $form['log_cache_tags'] = [
      '#type' => 'checkbox',
      '#title' => t('Log cache tag invalidations'),
      '#default_value' => $config->get('log_cache_tags'),
      '#description' => t('When enabled, Past will log cache tag invalidations including a backtrace. This should only be used to debug invalidations and can be slow when a lot of cache tag invalidations happen.'),
    ];

    $form['severity_threshold'] = [
      '#type'  => 'select',
      '#title' => t('Threshold'),
      '#description' => t('Allow to select a threshold severity for past events. Less severe events will not be saved.'),
      '#default_value' => $config->get('severity_threshold') ?: RfcLogLevel::DEBUG,
      '#options' => $severity_options,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $included_severity_levels = [];
    foreach ($form_state->getValue('backtrace_include') as $level => $enabled) {
      if ($enabled) {
        // Cutoff severity_ prefix again.
        $included_severity_levels[] = substr($level, 9);
      }
    }
    $this->config('past.settings')
      ->set('events_expire', $form_state->getValue('events_expire'))
      ->set('exception_handling', $form_state->getValue('exception_handling'))
      ->set('log_watchdog', $form_state->getValue('log_watchdog'))
      ->set('log_cache_tags', $form_state->getValue('log_cache_tags'))
      ->set('backtrace_include', $included_severity_levels)
      ->set('shutdown_handling', $form_state->getValue('shutdown_handling'))
      ->set('log_session_id', $form_state->getValue('log_session_id'))
      ->set('severity_threshold', $form_state->getValue('severity_threshold'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
