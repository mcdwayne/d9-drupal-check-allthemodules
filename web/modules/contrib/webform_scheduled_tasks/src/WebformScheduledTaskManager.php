<?php

namespace Drupal\webform_scheduled_tasks;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\webform_scheduled_tasks\Annotation\WebformScheduledTask;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\TaskPluginInterface;

/**
 * A plugin manager for tasks.
 */
class WebformScheduledTaskManager extends DefaultPluginManager {

  /**
   * Constructs a new class instance.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WebformScheduledTasks/Task', $namespaces, $module_handler, TaskPluginInterface::class, WebformScheduledTask::class);
    $this->alterInfo('webform_scheduled_tasks_task_info');
    $this->setCacheBackend($cache_backend, 'webform_scheduled_tasks_task_info', ['webform_scheduled_tasks_task_plugins']);
  }

}
