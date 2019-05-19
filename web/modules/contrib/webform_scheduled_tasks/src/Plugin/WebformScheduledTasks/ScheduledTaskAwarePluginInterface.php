<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks;

use Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface;

/**
 * Interface for a plugin that can set the scheduled task as context.
 */
interface ScheduledTaskAwarePluginInterface {

  /**
   * Set the scheduled task as context for this plugin.
   *
   * @param \Drupal\webform_scheduled_tasks\Entity\WebformScheduledTaskInterface $scheduledTask
   *   The scheduled task for context.
   *
   * @return $this
   */
  public function setScheduledTask(WebformScheduledTaskInterface $scheduledTask);

  /**
   * Get the scheduled task.
   *
   * @return \Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ScheduledTaskNotifyInterface
   *   A scheduled task.
   *
   * @throws \Exception
   *   Thrown if no task has been set.
   */
  public function getScheduledTask();

}
