<?php

namespace Drupal\visualn\Manager;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the VisualN Setup Baker plugin manager.
 */
class SetupBakerManager extends DefaultPluginManager {


  /**
   * Constructs a new SetupBakerManager object.
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
    parent::__construct('Plugin/VisualN/SetupBaker', $namespaces, $module_handler, 'Drupal\visualn\Core\SetupBakerInterface', 'Drupal\visualn\Annotation\VisualNSetupBaker');

    $this->alterInfo('visualn_setup_baker_info');
    $this->setCacheBackend($cache_backend, 'visualn_setup_baker_plugins');
  }

}
