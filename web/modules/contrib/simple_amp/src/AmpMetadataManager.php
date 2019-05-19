<?php

namespace Drupal\simple_amp;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * AmpMetadata plugin manager.
 *
 * @package Drupal\simple_amp
 */
class AmpMetadataManager extends DefaultPluginManager {

  /**
   * Constructs an AmpMetadataManager object.
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
    parent::__construct('Plugin/AmpMetadata', $namespaces, $module_handler, 'Drupal\simple_amp\AmpMetadataInterface', 'Drupal\simple_amp\Annotation\AmpMetadata');
    $this->alterInfo('amp_metadata_info');
    $this->setCacheBackend($cache_backend, 'amp_metadata');
  }

}
