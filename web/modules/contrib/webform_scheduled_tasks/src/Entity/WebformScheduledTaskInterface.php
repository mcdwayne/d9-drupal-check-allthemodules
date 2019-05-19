<?php

namespace Drupal\webform_scheduled_tasks\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * An interface for the scheduled tasks.
 */
interface WebformScheduledTaskInterface extends ConfigEntityInterface {

  /**
   * Get the task plugin.
   *
   * @return \Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\TaskPluginInterface
   *   The task plugin.
   */
  public function getTaskPlugin();

  /**
   * Get the result set plugin.
   *
   * @return \Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSetPluginInterface
   *   The result set plugin.
   */
  public function getResultSetPlugin();

  /**
   * Get the interval the admin has configured to run the task at.
   *
   * @return int
   *   The interval.
   */
  public function getRunIntervalAmount();

  /**
   * Get a multiplier for the run interval.
   *
   * This is set in instances where admins configure a multiple of a number of
   * seconds to run the task. For example, if they configured to run every 6
   * days, the multiple would 6 x seconds in 1 day.
   *
   * @return int
   *   The multiplier for the run interval.
   */
  public function getRunIntervalMultiplier();

  /**
   * Increment the next task run date by the current time + the run interval.
   */
  public function incrementTaskRunDateByInterval();

  /**
   * Register a successful task run.
   */
  public function registerSuccessfulTask();

  /**
   * Register a failed task run.
   */
  public function registerFailedTask(\Exception $e = NULL);

  /**
   * Get the webform this task is associated with.
   *
   * @return \Drupal\webform\WebformInterface
   *   The associated webform.
   */
  public function getWebform();

  /**
   * Set the date and time the next task is scheduled to be run.
   *
   * NOTE: setting this value will take effect immediately and does not require
   * an entity save.
   *
   * @param int $timestamp
   *   The timestamp for when the task will next be attempted to run.
   *
   * @return $this
   */
  public function setNextTaskRunDate($timestamp);

  /**
   * Halt a scheduled task and provide a reason.
   *
   * NOTE: this will take effect immediately and does not require an entity
   * save.
   *
   * @param string $reason
   *   The reason the task has been halted.
   *
   * @return $this
   */
  public function halt($reason = '');

  /**
   * Resume a schedule.
   *
   * NOTE: this will take effect immediately and does not require an entity
   * save.
   *
   * @return $this
   */
  public function resume();

  /**
   * Check if a task has been halted.
   *
   * @return bool
   *   TRUE if the task was halted, FALSE otherwise.
   */
  public function isHalted();

  /**
   * Get the reason a task was halted.
   *
   * @return string
   *   The reason a task was halted.
   *
   * @throws \Exception
   *   Throws an exception if the task has not been halted.
   */
  public function getHaltedReason();

  /**
   * Get the next run date for the task.
   *
   * @return int
   *   A timestamp for when the task will run next.
   */
  public function getNextTaskRunDate();

}
