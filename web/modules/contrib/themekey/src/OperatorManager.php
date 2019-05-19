<?php
/**
 * @file
 * Contains OperatorManager.
 */

namespace Drupal\themekey;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * ThemeKey Operator plugin manager.
 */
class OperatorManager extends DefaultPluginManager {

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
    parent::__construct('Plugin/Operator', $namespaces, $module_handler, 'Drupal\themekey\OperatorInterface', 'Drupal\themekey\Annotation\Operator');

    $this->setCacheBackend($cache_backend, 'themekey_operators');
  }
}
