<?php

namespace Drupal\permutive\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Permutive plugin manager.
 */
class PermutiveManager extends DefaultPluginManager {

  /**
   * Constructs a new PermutiveManager object.
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
    parent::__construct('Plugin/Permutive', $namespaces, $module_handler, 'Drupal\permutive\Plugin\PermutiveInterface', 'Drupal\permutive\Annotation\Permutive');

    $this->alterInfo('permutive_permutive_info');
    $this->setCacheBackend($cache_backend, 'permutive_permutive_plugins');
  }

}
