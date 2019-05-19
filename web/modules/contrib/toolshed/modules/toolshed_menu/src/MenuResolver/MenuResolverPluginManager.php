<?php

namespace Drupal\toolshed_menu\MenuResolver;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for managing menu resolvers.
 *
 * @see \Drupal\toolshed_menu\Annotation\MenuResolverAnnotation
 * @see \Drupal\toolshed_menu\MenuResolver\MenuResolverInterface
 * @see plugin_api
 */
class MenuResolverPluginManager extends DefaultPluginManager {

  /**
   * Constructs a MenuResolverPluginManager object.
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
      'Plugin/Toolshed/MenuResolver',
      $namespaces,
      $module_handler,
      'Drupal\toolshed_menu\MenuResolver\MenuResolverInterface',
      'Drupal\toolshed_menu\Annotation\MenuResolver'
    );

    $this->alterInfo('toolshed_menu_resolver_info');
    $this->setCacheBackend($cache_backend, 'toolshed_menu_resolver_info_plugins');
  }

}
