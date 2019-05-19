<?php

/**
 * @file
 * Contains \Drupal\wisski_salz\EngineManager.
 */

namespace Drupal\wisski_salz;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Engine plugin manager.
 */
class EngineManager extends DefaultPluginManager {
  /**
   * Constructs an EngineManager object.
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
//    dpm(func_get_args(),__METHOD__);
    parent::__construct(
      'Plugin/wisski_salz/Engine',
      $namespaces,
      $module_handler,
      'Drupal\wisski_salz\EngineInterface',
      'Drupal\wisski_salz\Annotation\Engine'
    );
    $this->alterInfo('wisski_salz_engine');
  }  
}
