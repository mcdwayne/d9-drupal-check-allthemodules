<?php

namespace Drupal\simple_a_b;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * SimpleAB Reporting plugin manager.
 */
class SimpleABReportingManger extends DefaultPluginManager {

  /**
   * Constructs an SimpleABReportingManger object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/SimpleABReport', $namespaces, $module_handler, 'Drupal\simple_a_b\SimpleABReportingInterface', 'Drupal\simple_a_b\Annotation\SimpleABReport');

    $this->alterInfo('simple_a_b_reports_info');
    $this->setCacheBackend($cache_backend, 'simple_a_b_reports');
  }

}
