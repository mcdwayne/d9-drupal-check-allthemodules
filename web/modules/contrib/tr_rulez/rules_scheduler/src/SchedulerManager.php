<?php

namespace Drupal\rules_scheduler;

use Drupal\Core\Queue\QueueFactory;
use Drupal\rules_scheduler\Entity\Task;

/**
 * Provides an implementation of the rules_scheduler.manager service.
 */
class SchedulerManager implements SchedulerManagerInterface {

  /**
   * The execution queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * Constructs a new SchedulerManager.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(QueueFactory $queue_factory) {
    $this->queue = $queue_factory->get('rules_scheduler_tasks');
  }

  /**
   * {@inheritdoc}
   */
  public function queueTasks() {
    $tasks = Task::loadReadyToRun();
    // D7 code limits adding tasks to 1000 per cron run. I don't know if that's
    // still necessary, but if so it would be done here by adding a counter to
    // this this loop and breaking the loop when the counter reaches its limit.
    foreach ($tasks as $task) {
      // Add the task to the queue and then remove it from the scheduler table.
      if ($this->queue->createItem($task)) {
        // Deletes the $task from the rules_scheduler table.
        // rules_scheduler_task_handler($task)->afterTaskQueued();
        $task->delete();
      }
    }

    // If we have queued tasks, then ensure Rules logging is done.
    if (!empty($tasks)) {
      // hook_exit() is not invoked for cron runs, so register it as shutdown
      // callback for logging the rules log to the watchdog.
  //    drupal_register_shutdown_function('rules_exit');
      // Clear the log before running tasks via the queue to avoid logging
      // unrelated logs from previous cron-operations.
  //    RulesLog::logger()->clear();
    }
  }

}
