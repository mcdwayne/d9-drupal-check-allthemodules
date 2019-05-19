<?php
/**
 * @file
 * Contains PropertyManager.
 */

namespace Drupal\themekey;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * ThemeKey Property plugin manager.
 */
class PropertyManager extends DefaultPluginManager {

  /**
   * Constructs an PropertyManager object.
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
    parent::__construct('Plugin/Property', $namespaces, $module_handler, 'Drupal\themekey\PropertyInterface', 'Drupal\themekey\Annotation\Property');

    $this->setCacheBackend($cache_backend, 'themekey_properties');
  }
}
