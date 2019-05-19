<?php
/**
 * @file
 * Contains \Drupal\task\TaskActionManager.
 */
namespace Drupal\task;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
/**
 * Manages task plugins.
 */
class TaskActionManager extends DefaultPluginManager {
  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // This tells the plugin system to look for plugins in the 'Plugin/task/Action' subfolder inside modules' 'src' folder.
    $subdir = 'Plugin/task/Action';
    // The name of the interface that plugins should adhere to.  Drupal will enforce this as a requirement.
    $plugin_interface = 'Drupal\task\TaskActionInterface';
    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = 'Drupal\task\Annotation\TaskAction';
    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);
    // This allows the plugin definitions to be altered by an alter hook. The parameter defines the name of the hook, thus: task_action_info_alter().
    $this->alterInfo('task_action_info');
    // This sets the caching method for our plugin definitions.
    $this->setCacheBackend($cache_backend, 'task_action_info');
  }
}