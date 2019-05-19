<?php

namespace Drupal\webform_scheduled_tasks;

use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;

/**
 * An interface for managing the scheduling state of tasks.
 */
interface SchedulingStateInterface {

  /**
   * Check if a time has been scheduled for a task to be run.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The scheduled task.
   *
   * @return bool
   *   TRUE if a time has been scheduled, FALSE otherwise.
   */
  public function hasTimeScheduled(WebformScheduledTaskInterface $scheduledTask);

  /**
   * Get the next time a task is scheduled to run.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The scheduled task.
   *
   * @return int|null
   *   The timestamp for the next scheduled time the task should run.
   */
  public function getNextScheduledTime(WebformScheduledTaskInterface $scheduledTask);

  /**
   * Set the next scheduled time a task should run.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The scheduled task.
   * @param int $timestamp
   *   The timestmap.
   */
  public function setNextScheduledTime(WebformScheduledTaskInterface $scheduledTask, $timestamp);

  /**
   * Halt a scheduled task.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The task.
   * @param string $message
   *   (Optional) The message to provide the user.
   */
  public function haltTask(WebformScheduledTaskInterface $scheduledTask, $message = '');

  /**
   * Check if a task has been halted.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The task.
   *
   * @return bool
   *   TRUE if a task has been halted, FALSE otherwise.
   */
  public function isHalted(WebformScheduledTaskInterface $scheduledTask);

  /**
   * Get the message associated with a halted task, if one exists.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The task.
   *
   * @return string
   *   The message provided when halting the task.
   *
   * @throws \Exception
   *   Thrown when a task has not been halted.
   */
  public function getHaltedMessage(WebformScheduledTaskInterface $scheduledTask);

  /**
   * Resume a task.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The task.
   */
  public function resumeTask(WebformScheduledTaskInterface $scheduledTask);

}
