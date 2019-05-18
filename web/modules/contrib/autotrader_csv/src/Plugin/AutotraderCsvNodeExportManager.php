<?php

namespace Drupal\autotrader_csv\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Autotrader CSV Node Export plugin manager.
 */
class AutotraderCsvNodeExportManager extends DefaultPluginManager {

  /**
   * Constructs a new AutotraderCsvNodeExportManager object.
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
    parent::__construct('Plugin/AutotraderCsvNodeExport', $namespaces, $module_handler, 'Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportInterface', 'Drupal\autotrader_csv\Annotation\AutotraderCsvNodeExport');

    $this->alterInfo('autotrader_csv_autotrader_csv_node_export_info');
    $this->setCacheBackend($cache_backend, 'autotrader_csv_autotrader_csv_node_export_plugins');
  }

}
