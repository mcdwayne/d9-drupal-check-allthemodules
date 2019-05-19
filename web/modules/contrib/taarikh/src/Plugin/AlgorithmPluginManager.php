<?php

namespace Drupal\taarikh\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for taarikh algorithms.
 *
 * @see \Drupal\taarikh\Annotation\TaarikhAlgorithm
 * @see \Drupal\taarikh\TaarikhAlgorithmPluginInterface
 * @see plugin_api
 */
class AlgorithmPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new AlgorithmPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TaarikhAlgorithm', $namespaces, $module_handler, 'Drupal\taarikh\TaarikhAlgorithmPluginInterface', 'Drupal\taarikh\Annotation\TaarikhAlgorithm');

    $this->alterInfo('taarikh_algorithm_info');
    $this->setCacheBackend($cache_backend, 'taarikh_algorithms');
  }

}
