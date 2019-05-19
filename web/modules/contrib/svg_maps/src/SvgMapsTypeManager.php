<?php

namespace Drupal\svg_maps;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\svg_maps\Annotation\SvgMapsType;

/**
 * Provides the Svg maps plugin plugin manager.
 */
class SvgMapsTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new SvgMapsTypeManager object.
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
    parent::__construct('Plugin/SvgMaps/Type', $namespaces, $module_handler, SvgMapsTypeInterface::class, SvgMapsType::class);
    $this->alterInfo('svg_maps_type_info');
    $this->setCacheBackend($cache_backend, 'svg_maps_type_plugins');
  }

}
