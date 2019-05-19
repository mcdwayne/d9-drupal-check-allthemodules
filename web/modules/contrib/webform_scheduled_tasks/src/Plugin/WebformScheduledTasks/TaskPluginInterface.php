<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * An interface for scheduled tasks.
 */
interface TaskPluginInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurablePluginInterface, ScheduledTaskNotifyInterface, ScheduledTaskAwarePluginInterface {

  /**
   * Execute a task.
   *
   * @param \Iterator $submissions
   *   An iterator of webform submissions to process.
   *
   * @throws \Exception
   * @throws \Drupal\webform_scheduled_tasks\Exception\RetryScheduledTaskException
   * @throws \Drupal\webform_scheduled_tasks\Exception\HaltScheduledTaskException
   */
  public function executeTask(\Iterator $submissions);

  /**
   * Get the label of the task.
   *
   * @return string
   *   The label of the task.
   */
  public function label();

}
