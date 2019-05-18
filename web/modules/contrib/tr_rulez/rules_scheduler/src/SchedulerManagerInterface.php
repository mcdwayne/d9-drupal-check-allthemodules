<?php

namespace Drupal\rules_scheduler;

/**
 * Defines a common interface for managing Rules scheduling.
 */
interface SchedulerManagerInterface {

  /**
   * Handler for hook_cron().
   *
   * Identifies scheduled Tasks that are ready to run (i.e., their scheduled
   * timestamp is in the past), then moves them to the 'rules_scheduler_tasks'
   * queue where they will be executed.
   */
  public function queueTasks();

}
