<?php

namespace Drupal\imagepin\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the manager for widget plugins.
 */
class WidgetManager extends DefaultPluginManager {

  /**
   * Constructor method.
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
    parent::__construct('Plugin/imagepin/Widget', $namespaces, $module_handler, 'Drupal\imagepin\Plugin\WidgetInterface', 'Drupal\imagepin\Annotation\Widget');
    $this->alterInfo('imagepin_widget');
    $this->setCacheBackend($cache_backend, 'imagepin_widget');
  }

}
