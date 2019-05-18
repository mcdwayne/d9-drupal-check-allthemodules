<?php

/**
 * @file
 * Hooks provided by the Raven module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter or suppress breadcrumbs before they are recorded.
 *
 * This hook can be used to alter breadcrumbs before they are recorded. The
 * breadcrumb can be ignored by setting $breadcrumb['process'] to FALSE.
 *
 * @param array $breadcrumb
 *   The parameters passed to the \Drupal\raven\Logger::log() method, as well as
 *   the parameters that will be passed to the \Raven_Breadcrumbs::record()
 *   method:
 *   - level: The log level passed to \Drupal\raven\Logger::log().
 *   - message: The log message passed to \Drupal\raven\Logger::log().
 *   - context: The original context array passed to Logger::log().
 *   - breadcrumb: Reference to the array to be passed to
 *     \Raven_Breadcrumbs::record().
 *   - process: If FALSE, the breadcrumb will not be recorded.
 *
 * @see \Drupal\raven\Logger\Raven::log()
 */
function hook_raven_breadcrumb_alter(array &$breadcrumb) {
  // Don't record any breadcrumbs.
  $breadcrumb['process'] = FALSE;
}

/**
 * Alter or suppress log messages before they are sent to Sentry.
 *
 * This hook can be used to alter the captured data before sending to Sentry.
 * The message can be ignored by setting $filter['process'] to FALSE.
 *
 * @param array $filter
 *   The parameters passed to the \Drupal\raven\Logger::log() method, as well as
 *   the parameters that will be passed to the \Raven_Client::capture() method:
 *   - level: The log level passed to \Drupal\raven\Logger::log().
 *   - message: The log message passed to \Drupal\raven\Logger::log().
 *   - context: The original context array passed to Logger::log().
 *   - data: Reference to data array to be passed to \Raven_Client::capture().
 *   - stack: The PHP backtrace to be passed to \Raven_Client::capture().
 *   - client: The Raven client object.
 *   - process: If FALSE, the message will not be sent to Sentry.
 *
 * @see \Drupal\raven\Logger\Raven::log()
 */
function hook_raven_filter_alter(array &$filter) {
  // Ignore Sentry logging for certain Rest notices.
  $ignore['rest'] = [
    'Created entity %type with ID %id.',
    'Updated entity %type with ID %id.',
    'Deleted entity %type with ID %id.',
  ];
  foreach ($ignore as $channel => $messages) {
    if ($filter['context']['channel'] === $channel && in_array($filter['message'], $messages)) {
      $filter['process'] = FALSE;
    }
  }
}

/**
 * Alter Sentry PHP client options.
 *
 * @param array $options
 *   The options to be passed to Raven_Client::__construct().
 *
 * @see \Drupal\raven\Logger\Raven::log()
 */
function hook_raven_options_alter(array &$options) {
  $options['environment'] = getenv('SENTRY_CURRENT_ENV');
}

/**
 * @} End of "addtogroup hooks".
 */
