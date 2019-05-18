<?php

/**
 * @file
 * Contains \Drupal\bideo\BideoPluginManager.
 */

namespace Drupal\bideo;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines a plugin manager for bideo plugins.
 */
class BideoPluginManager extends DefaultPluginManager {

  /**
   * Constructs a BideoPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Bideo', $namespaces, $module_handler);
    $this->alterInfo('bideo_plugins');
    $this->setCacheBackend($cache_backend, "bideo_plugins", array('extension','extension:bideo'));
  }

}
