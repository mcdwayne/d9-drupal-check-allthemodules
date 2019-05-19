<?php

/**
 * @file
 * Drush commands for votingapi_queue.
 */

use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\QueueWorkerInterface;

/**
 * Implements hook_drush_command().
 */
function votingapi_queue_drush_command() {
  $commands = array();

  $commands['votingapi-queue-run'] = [
    'description' => dt('Starts a new worker process for the votingapi Queue.'),
    'core' => ['8+'],
    'options' => [
      'time-limit' => dt('The time limit. Default is set to value 0, which is unlimited.'),
      'run-once' => dt('If passed terminates once queue is empty. Time limit is ignored.'),
    ],
  ];
  $commands['votingapi-queue-populate'] = [
    'description' => dt('Populate the votingapi queue with items for processing..'),
    'core' => ['8+'],
    'arguments' => [
      'limit' => dt('The maximum amount to be processed in one run. (optional)'),
    ],
  ];
  $commands['votingapi-queue-set-last-run-time'] = [
    'description' => dt('Sets the last executed run time.'),
    'core' => ['8+'],
    'arguments' => [
      'timestamp' => dt('The timestamp (integer) to be set. Pass string FALSE to unset the time.'),
    ],
  ];
  $commands['votingapi-queue-debug'] = [
    'description' => dt('Prints some health and debug info. Containing last execution timestamp and page.'),
    'core' => ['8+'],
  ];

  return $commands;
}

/**
 * Processes the votingapi queue.
 */
function drush_votingapi_queue_run() {
  $time_limit = drush_get_option('time-limit', 0);
  $run_once = drush_get_option('run-once', 0);
  $queue = \Drupal::queue('votingapi_queue');
  $queue_worker = \Drupal::service('plugin.manager.queue_worker')
    ->createInstance('votingapi_queue');

  if ($time_limit) {
    $end = time() + $time_limit;
    while (time() < $end) {
      $status = _votingapi_queue_process_next_item($queue, $queue_worker, $time_limit);
      if (!$status && $run_once) {
        break;
      }
    }
  }
  else {
    while (TRUE) {
      $status = _votingapi_queue_process_next_item($queue, $queue_worker);
      if (!$status && $run_once) {
        break;
      }
    }
  }

  drush_print(dt("Finished votingapi queue run."));
}

/**
 * Helper function to process the next available item.
 *
 * When no item could be claimed,
 * a sleep for one second will be invoked
 * to reduce the possible overhead of database calls.
 */
function _votingapi_queue_process_next_item(QueueInterface $queue, QueueWorkerInterface $queue_worker, $lease_time = 30) {
  if ($item = $queue->claimItem($lease_time)) {
    try {
      // Remove the item from the queue,
      // no matter whether it has been processed successfully.
      $queue->deleteItem($item);
      $queue_worker->processItem($item->data);
      return TRUE;
    }
    catch (\Exception $e) {
      watchdog_exception('cron', $e);
    }
  }
  else {
    sleep(1);
    return FALSE;
  }
}

/**
 * Adds items to queue.
 *
 * @param int|false $limit
 *   Maximum items to be processed or FALSE for every available item.
 */
function drush_votingapi_queue_populate($limit = FALSE) {
  if (FALSE === $limit) {
    drush_print(dt("Adding all available items for processing."));
  }
  else {
    drush_print(dt("Adding @limit items for processing.", ['@limit' => $limit]));
  }
  _votingapi_queue_populate($limit);
}

/**
 * Sets or resets the last run queue cron time and next page.
 *
 * @param string|int $timestamp
 *   string containing FALSE or unix timestamp as integer.
 */
function drush_votingapi_queue_set_last_run_time($timestamp) {
  $state = Drupal::state();
  if (strtoupper($timestamp) == 'FALSE') {
    $state->delete('votingapi.queue_next_page');
    drush_print(dt("Deleted votingapi.queue_next_page."));
    $state->delete('votingapi.queue_last_run');
    drush_print(dt("Deleted votingapi.queue_last_run."));
  }
  else {
    $state->delete('votingapi.queue_next_page');
    drush_print(dt("Deleted votingapi.queue_next_page."));
    $state->set('votingapi.queue_last_run', $timestamp);
    drush_print(dt("set votingapi.queue_last_run to $timestamp"));
  }
}

/**
 * Validation function to check parameters.
 *
 * @see drush_votingapi_queue_set_last_run_time()
 */
function drush_votingapi_queue_set_last_run_time_validate() {
  $args_count = func_num_args();
  if ($args_count == 0) {
    return drush_set_error('ARGUMENTS_REQUIRED', dt('Please provide timestamp or FALSE as argument.'));
  }
  else {
    $timestamp = func_get_arg(0);
  }

  if (strtoupper($timestamp) == 'FALSE') {
    return TRUE;
  }
  if (!is_int($timestamp)) {
    return drush_set_error('ARGUMENTS_REQUIRED', dt('Please provide integer timestmap or FALSE string as argument.'));
  }
}

/**
 * Displays debug / health information about the votingapi.
 */
function drush_votingapi_queue_debug() {
  $state = Drupal::state();
  drush_print(dt(
    "votingapi.queue_last_run: @value", [
      '@value' => $state->get('votingapi.queue_last_run'),
    ]
  ));
  drush_print(dt(
    "votingapi.queue_next_page: @value", [
      '@value' => $state->get('votingapi.queue_next_page'),
    ]
  ));
}
