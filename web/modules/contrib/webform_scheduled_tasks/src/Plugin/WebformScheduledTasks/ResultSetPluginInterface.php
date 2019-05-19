<?php

namespace Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * An interface for result set plugins.
 */
interface ResultSetPluginInterface extends PluginInspectionInterface, PluginFormInterface, ConfigurablePluginInterface, ScheduledTaskNotifyInterface, ScheduledTaskAwarePluginInterface {

  /**
   * Get an iterator for a set of results matching the conditions of the plugin.
   *
   * @return \Iterator
   *   An iterator for all results that match the conditions of the plugin.
   */
  public function getResultSet();

  /**
   * Get the label of the result plugin.
   *
   * @return string
   *   The label of the task.
   */
  public function label();

}
