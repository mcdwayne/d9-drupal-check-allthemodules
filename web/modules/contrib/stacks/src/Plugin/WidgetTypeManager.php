<?php

namespace Drupal\stacks\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class WidgetTypeManager.
 * @package Drupal\stacks\Plugin
 */
class WidgetTypeManager extends DefaultPluginManager {

  protected $fields;

  /**
   * Constructs a WidgetTypeManager object.
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
    parent::__construct('Plugin/WidgetType', $namespaces, $module_handler, 'Drupal\stacks\Plugin\WidgetTypeInterface', 'Drupal\stacks\Annotation\WidgetType');
    $this->alterInfo('stacks_widget_type');
    $this->setCacheBackend($cache_backend, 'stacks_widget_type');
  }

  /**
   * @return array
   */
  public function getDefinitionsOptions() {
    $plugin_definitions = $this->getDefinitions();
    $options = [];
    foreach ($plugin_definitions as $definition) {
      $options[$definition['id']] = $definition['label'];
    }

    return $options;
  }

}
