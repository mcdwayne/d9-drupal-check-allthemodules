<?php

/**
 * @file
 * Contains \Drupal\quick_pages\MainContentPluginManager.
 */

namespace Drupal\quick_pages;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * MainContentPluginManager description.
 */
class MainContentPluginManager extends DefaultPluginManager {

  /**
   * Constructs the manageg object.
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
    parent::__construct(
      'Plugin/QuickPages/MainContent',
      $namespaces,
      $module_handler,
      'Drupal\quick_pages\MainContentInterface',
      'Drupal\quick_pages\Annotation\MainContent'
    );
    $this->alterInfo('main_content_provider_info');
  }

}
