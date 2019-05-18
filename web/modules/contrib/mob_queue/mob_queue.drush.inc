<?php
/**
 * @file
 * Drush commands for Drush Queue Handling.
 */

/**
 * Implements hook_drush_command().
 */
function mob_queue_drush_command() {
  $items = array();

  $items['mob-exe-queue'] = array(
    'description' => "Execute mob_queue queued tasks.",
    'examples' => array(
      'drush mob-queue' => 'Go a sprint to finish the tasks in mob_queue queues.',
    ),
    'arguments' => array(
      'time' => dt('Total execution time for this command.'),
    ),
    'options' => array(
      'no-reset-expired' => "Do not reset expired items from the queue table.",
    ),
    'aliases' => array('meq'),
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function mob_queue_drush_help($section) {
  switch ($section) {
    case 'drush:mob-queue':
      return dt("Execute queue tasks.");
  }
}

/**
 * Run the queued job.
 */
function drush_mob_queue_mob_exe_queue($time = 900) {
  // Allow execution to continue even if the request gets canceled.
  @ignore_user_abort(TRUE);

  if (!drush_get_option('no-reset-expired', FALSE)) {
    // Reset expired items in the default queue implementation table.
    $updated = db_update('queue')
      ->fields(array(
        'expire' => 0,
      ))
      ->condition('expire', 0, '<>')
      ->condition('expire', REQUEST_TIME, '<')
      ->execute();
    drush_log(dt('!updated expired items reset.', array('!updated' => $updated)));
  }

  // Force the current user to anonymous to ensure consistent permissions on
  // cron runs.
  $accountSwitcher = \Drupal::service('account_switcher');
  $accountSwitcher->switchTo(new Drupal\Core\Session\AnonymousUserSession());

  // Try to allocate enough time to run all the hook_cron implementations.
  drupal_set_time_limit($time);

  // Grab the defined cron queues.
  //$queues = \Drupal::moduleHandler()->invokeAll('cron_queue_info');
  $queues = \Drupal::service('mob_queue.operator')->getQueueJobs();
  \Drupal::moduleHandler()->alter('mob_queue_cron_queue_info', $queues);

  foreach ($queues as $queue_name => $info) {
//    $this->queueFactory->get($queue_name)->createQueue();
  }

  reset($queues);
  while (TRUE) {
    // Due to backwards incompatible changes on array handling from PHP 5.x to
    // 7.x, looping code must be kept simple. For instance, current item pointer
    // should be advanced manually, since foreach() does not starting from 7.0.
    // @see #2974823 for more info.
    $queue_name = key($queues);
    $info = current($queues);
    if ($queue_name === NULL && $info === FALSE) {
      break;
    }
    next($queues);

    // Allow other modules to alter the queues listing, order queue to process.
    \Drupal::moduleHandler()->alter('mob_queue_queue_processing', $queue_name, $info, $queues);

    \Drupal::service('mob_queue.operator')->processQueues($queue_name, $info);

    \Drupal::moduleHandler()->invokeAll('mob_queue_queue_processed', [$queue_name, $info, $queues]);
  }

  // Restore the user.
  $accountSwitcher->switchBack();

}
