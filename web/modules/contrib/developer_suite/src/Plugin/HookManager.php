<?php

namespace Drupal\developer_suite\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;

/**
 * Class HookManager.
 *
 * Provides hook plugin manager.
 *
 * @see \Drupal\developer_suite\Annotation\Hook
 * @see plugin_api
 */
class HookManager extends DefaultPluginManager {

  /**
   * HookManager constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin
   *   implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces,
    CacheBackendInterface $cacheBackend,
    ModuleHandlerInterface $moduleHandler) {
    parent::__construct(
      'Plugin/Hook',
      $namespaces,
      $moduleHandler,
      NULL,
      'Drupal\developer_suite\Annotation\Hook'
    );
  }

}
