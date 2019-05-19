<?php

namespace Drupal\webform_scheduled_tasks;

/**
 * An interface for the task runner.
 */
interface TaskRunnerInterface {

  /**
   * Execute all pending tasks.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface[] $tasks
   *   A list of tasks to execute, regardless of their eligibility.
   */
  public function executeTasks(array $tasks);

  /**
   * Get a list of pending tasks ready to be executed based on the schedule.
   *
   * @return \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface[]
   *   A list of tasks ready to execute based on the schedule.
   */
  public function getPendingTasks();

}
