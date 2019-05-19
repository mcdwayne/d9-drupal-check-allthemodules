<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks;

/**
 * An interface for scheduled task plugins that are notified.
 */
interface ScheduledTaskNotifyInterface {

  /**
   * Called when a task is successful.
   */
  public function onSuccess();

  /**
   * Called when a task fails.
   */
  public function onFailure();

}
