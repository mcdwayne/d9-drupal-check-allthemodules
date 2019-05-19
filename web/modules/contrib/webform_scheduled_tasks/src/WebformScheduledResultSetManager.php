<?php

namespace Drupal\webform_scheduled_tasks;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\webform_scheduled_tasks\Annotation\WebformScheduledResultSet;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\ResultSetPluginInterface;

/**
 * A plugin manager for result sets.
 */
class WebformScheduledResultSetManager extends DefaultPluginManager {

  /**
   * Constructs a new class instance.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WebformScheduledTasks/ResultSet', $namespaces, $module_handler, ResultSetPluginInterface::class, WebformScheduledResultSet::class);
    $this->alterInfo('webform_scheduled_tasks_result_set_info');
    $this->setCacheBackend($cache_backend, 'webform_scheduled_tasks_result_set_info', ['webform_scheduled_tasks_result_set_plugins']);
  }

}
