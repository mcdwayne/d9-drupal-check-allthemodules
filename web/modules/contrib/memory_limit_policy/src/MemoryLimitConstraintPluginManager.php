<?php

namespace Drupal\memory_limit_policy;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Plugin manager that controls memory limit constraints.
 */
class MemoryLimitConstraintPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new MemoryLimitConstraintPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/MemoryLimitConstraint', $namespaces, $module_handler, 'Drupal\memory_limit_policy\MemoryLimitConstraintInterface', 'Drupal\memory_limit_policy\Annotation\MemoryLimitConstraint');
    $this->alterInfo('memory_limit_policy_constraint_info');
    $this->setCacheBackend($cache_backend, 'memory_limit_policy_constraint');
  }

}
