<?php

namespace Drupal\error_log\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Implements an Error Log config form.
 */
class ErrorLogConfigForm {

  /**
   * Builds Error Log config form.
   */
  public static function buildForm(array &$form) {
    $config = \Drupal::config('error_log.settings');
    $form['error_log'] = [
      '#type'          => 'details',
      '#title'         => t('Error Log'),
      '#tree'          => TRUE,
      '#open'          => TRUE,
      '#description'   => error_log_help('help.page.error_log'),
    ];
    foreach (RfcLogLevel::getLevels() as $key => $value) {
      $options["level_$key"] = $value;
    }
    foreach ($config->get('log_levels') as $key => $value) {
      $default_value[$key] = $value ? $key : 0;
    }
    $form['error_log']['log_levels'] = [
      '#type'          => 'checkboxes',
      '#title'         => t('Log levels'),
      '#description'   => t('Check the log levels which should be sent to the PHP error log.'),
      '#options'       => $options,
      '#default_value' => $default_value,
    ];
    $form['error_log']['ignored_channels'] = [
      '#type'          => 'textarea',
      '#title'         => t('Ignored channels'),
      '#description'   => t('A list of log channels for which messages should not be sent to the PHP error log (one channel per line). Commonly-configured log channels include <em>access denied</em> for 403 errors and <em>page not found</em> for 404 errors.'),
      '#default_value' => implode("\n", $config->get('ignored_channels') ?: []),
    ];
    $form['#submit'][] = 'Drupal\error_log\Form\ErrorLogConfigForm::submitForm';
  }

  /**
   * Submits Error Log config form.
   */
  public static function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::configFactory()->getEditable('error_log.settings')
      ->set('log_levels', $form_state->getValue(['error_log', 'log_levels']))
      ->set('ignored_channels', array_map('trim', preg_split('/\R/', $form_state->getValue(['error_log', 'ignored_channels']), -1, PREG_SPLIT_NO_EMPTY)))
      ->save();
  }

}
