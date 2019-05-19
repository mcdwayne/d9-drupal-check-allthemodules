<?php

namespace Drupal\simple_a_b;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * SimpleAB Entity test plugin manager.
 */
class SimpleABTypeManger extends DefaultPluginManager {

  /**
   * Constructs an SimpleABTypeManger object.
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
    parent::__construct('Plugin/SimpleABType', $namespaces, $module_handler, 'Drupal\simple_a_b\SimpleABTypeInterface', 'Drupal\simple_a_b\Annotation\SimpleABType');

    $this->alterInfo('simple_a_b_type_info');
    $this->setCacheBackend($cache_backend, 'simple_a_b_types');
  }

}
