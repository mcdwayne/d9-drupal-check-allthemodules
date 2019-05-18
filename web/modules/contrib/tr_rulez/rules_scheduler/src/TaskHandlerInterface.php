<?php

namespace Drupal\rules_scheduler;

/**
 * Interface for scheduled task handlers.
 *
 * Task handlers control the behavior of a task when it's queued or executed.
 * Unless specified otherwise, the DefaultTaskHandler task handler is used.
 *
 * @see rules_scheduler_run_task()
 * @see rules_scheduler_cron()
 * @see \Drupal\rules_scheduler\DefaultTaskHandler
 */
interface TaskHandlerInterface {

  /**
   * Processes a queue item.
   *
   * @throws \Drupal\rules\Exception\EvaluationException
   *   If there are any problems executing the task.
   *
   * @see rules_scheduler_run_task()
   */
  public function runTask();

  /**
   * Processes a task after it has been queued.
   *
   * @see rules_scheduler_cron()
   */
  public function afterTaskQueued();

  /**
   * Returns the task associated with the task handler.
   *
   * @return \Drupal\rules_scheduler\Entity\TaskInterface
   *   The task.
   */
  public function getTask();

}
