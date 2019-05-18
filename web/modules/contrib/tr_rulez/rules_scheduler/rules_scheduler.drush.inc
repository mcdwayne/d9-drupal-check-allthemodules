<?php

/**
 * @file
 * Rules Scheduler drush integration.
 */

/**
 * Implements hook_drush_help().
 */
function rules_scheduler_drush_help($section) {
  switch ($section) {
    case 'meta:rules_scheduler:title':
      return dt('Rules Scheduler commands');

    case 'meta:rules_scheduler:summary':
      return dt('Manages scheduling the execution of Rules components.');
  }
}

/**
 * Implements hook_drush_command().
 */
function rules_scheduler_drush_command() {
  $items = [];

  $items['rules-scheduler-tasks'] = [
    'description' => dt('Check for scheduled tasks to be added to the queue.'),
    'options' => [
      'claim' => 'Optionally claim tasks from the queue to work on. Any value set will override the default time spent on this queue.',
    ],
    'drupal dependencies' => ['rules', 'rules_scheduler'],
    'core' => ['8+'],
    'aliases' => ['rusch'],
    'examples' => [
      'drush rusch' => 'Add scheduled tasks to the queue.',
      'drush rusch --claim' => 'Add scheduled tasks to the queue and claim items for the default amount of time.',
      'drush rusch --claim=30' => 'Add scheduled tasks to the queue and claim items for 30 seconds.',
    ],
  ];

  return $items;
}

/**
 * Command callback for processing the rules_scheduler_tasks queue.
 *
 * @see rules_scheduler_cron()
 * @see \Drupa\Core\Cron::run()
 * @see \Drupal\rules_scheduler\Plugin\QueueWorker\TaskWorker
 */
function drush_rules_scheduler_tasks() {
  // Force Rules Scheduler to queue any ready-to-execute tasks.
  // This is exactly what Rules Scheduler does on a cron run, but
  // note this is being done OUTSIDE of hook_cron() - cron doesn't
  // trigger this, drush does.
  \Drupal::service('rules_scheduler.manager')->queueTasks();

  // Much of this code is taken from \Drupal\Core\Cron::processQueues().
  // It would be nice to avoid this duplication.
  $claim = drush_get_option('claim', FALSE);
  if ($claim) {
    // Fetch the queue information and worker plugin definition.
    $queue_name = 'rules_scheduler_tasks';
    $queueWorkerManager = \Drupal::service('plugin.manager.queue_worker');
    $info = $queueWorkerManager->getDefinition($queue_name);

    // The drush option can override the default process time.
    $default_time = isset($info['cron']['time']) ? isset($info['cron']['time']) : 15;
    $lease_time = is_numeric($claim) ? (int) $claim : $default_time;
    $end = time() + $lease_time;

    // Claim items and process the queue.
    $queue = \Drupal::queue($queue_name);
    $queueWorker = $queueWorkerManager->createInstance('rules_scheduler_tasks');

    $claimed = 0;
    while (time() < $end && ($item = $queue->claimItem($lease_time))) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
        $claimed++;
      }
      catch (RequeueException $e) {
        // The worker requested the task be immediately requeued.
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        // If the worker indicates there is a problem with the whole queue,
        // release the item and skip to the next queue.
        $queue->releaseItem($item);
        watchdog_exception('cron', $e);
        // Skip processing this queue.
        continue;
      }
      catch (\Exception $e) {
        // In case of any other kind of exception, log it and leave the item
        // in the queue to be processed again later.
        watchdog_exception('cron', $e);
      }
    }

    if ($claimed) {
      drush_log(dt('Claimed and worked on !claimed scheduled tasks for up to !time seconds.', ['!claimed' => $claimed, '!time' => $time]), 'success');
    }
  }
}
