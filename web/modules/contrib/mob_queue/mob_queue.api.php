<?php

/**
 * @file
 * Hooks provided by the Drush Queue Handling module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter cron queue information before mob-queue runs.
 *
 * Called by mob-queue to allow modules to alter cron queue
 * settings before any jobs are processesed.
 * It works the same as hook_cron_queue_info() but this
 * hook applies only to mob-queue, in case you want to alter
 * queues only under mob-queue execution.
 *
 * @param array $queue
 *   An array of cron queue information.
 *
 * @see hook_cron_queue_info_alter()
 */
function hook_mob_queue_cron_queue_info_alter(&$queues) {
  // Remove this queue from mob-queue's queue list at all.
  unset($queues['myqueue']);
}

/**
 * Alter queue information while mob-queue is running.
 *
 * Allows to alter the queue list and next-to-be-processed
 * queue information.
 * Caution: Make sure you understand how mob-queue works internally.
 * Most of the time, you should alter the $queue_name and $queue_info
 * variables in sync.
 * However, you can change the queue name parameter independent from
 * its info array and wreak havoc.
 *
 * @param string $queue_name
 *   The queue name.
 * @param array $queue_info
 *   The queue information array item as declared in the queue's
 *   hook_cron_queue_info().
 * @param array $queues
 *   An array of cron queue information.
 *
 * @see hook_cron_queue_info()
 */
function hook_mob_queue_queue_processing_alter(&$queue_name, &$queue_info, &$queues) {
  $some_priority_condition = TRUE;
  $a_more_important_queue = 'important-queue';
  if ($some_priority_condition) {
    $queue_name = $a_more_important_queue;
    $info = $queues[$a_more_important_queue];
  }
}

/**
 * A queue has been processed.
 *
 * @param string $queue_name
 *   The queue name.
 * @param array $queue_info
 *   The queue information array item as declared in the queue's
 *   hook_cron_queue_info().
 * @param array $queues
 *   An array of cron queue information.
 *
 * @see hook_cron_queue_info()
 */
function hook_mob_queue_queue_processed($queue_name, $info, $queues) {
  \Drupal::logger('mymodule')->notice('Queue @queue has been processed.', array('@queue' => $queue_name));
}

/**
 * @} End of "addtogroup hooks".
 */
