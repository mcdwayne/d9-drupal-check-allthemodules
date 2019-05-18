<?php

namespace Drupal\external_data_source\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the External Data Source plugin manager.
 */
class ExternalDataSourceManager extends DefaultPluginManager {

  /**
   * Constructs a new ExternalDataSource object.
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
    parent::__construct('Plugin/ExternalDataSource', $namespaces, $module_handler, 'Drupal\external_data_source\Plugin\ExternalDataSourceInterface', 'Drupal\external_data_source\Annotation\ExternalDataSource');

    $this->alterInfo('external_data_source_info');
    $this->setCacheBackend($cache_backend, 'external_data_source_plugins');
  }

}
