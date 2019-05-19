<?php

namespace Drupal\visualn\Manager;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Raw Resource Format plugin manager.
 */
class RawResourceFormatManager extends DefaultPluginManager {


  /**
   * Constructs a new RawResourceFormatManager object.
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
    parent::__construct('Plugin/VisualN/RawResourceFormat', $namespaces, $module_handler, 'Drupal\visualn\Core\RawResourceFormatInterface', 'Drupal\visualn\Annotation\VisualNRawResourceFormat');

    $this->alterInfo('visualn_raw_resource_format_info');
    $this->setCacheBackend($cache_backend, 'visualn_raw_resource_format_plugins');
  }

}
