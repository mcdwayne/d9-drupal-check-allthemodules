<?php

namespace Drupal\staged_content;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Detects and resolves which marker a given entity uses.
 *
 * This has been split of to a into a set of plugins to make it easier to swap
 * this out based on the exact needs of the implementor.
 */
class MarkerDetectorManager extends DefaultPluginManager implements MarkerDetectorManagerInterface {

  /**
   * Constructs a new \Drupal\rest\Plugin\Type\ResourcePluginManager object.
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
    parent::__construct('Plugin/StagedContent/Marker', $namespaces, $module_handler, 'Drupal\staged_content\Plugin\StagedContent\Marker\MarkerDetectorInterface', 'Drupal\staged_content\Annotation\MarkerDetector');

    $this->setCacheBackend($cache_backend, 'staged_content_marker_detectors');
    $this->alterInfo('staged_content_marker_detectors');
  }

}
