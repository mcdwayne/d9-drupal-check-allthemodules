<?php

namespace Drupal\contacts_events\Cron;

/**
 * Interface for cron services.
 */
interface CronInterface {

  /**
   * Check if this task is scheduled to run.
   *
   * @return bool
   *   Whether the task is due to run.
   */
  public function scheduledToRun();

  /**
   * Run the task if it due to run.
   */
  public function invokeOnSchedule();

  /**
   * Process the actual task, regardless of run time.
   */
  public function invoke();

}
