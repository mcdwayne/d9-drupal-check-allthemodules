<?php

namespace Drupal\integro;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\integro\Annotation\IntegroOperation;

/**
 * Manages discovery and instantiation of plugins.
 */
class OperationManager extends DefaultPluginManager implements OperationManagerInterface {

  /**
   * Constructs a new instance.
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
    parent::__construct('Plugin/Integro/Operation', $namespaces, $module_handler, OperationInterface::class, IntegroOperation::class);
    $this->alterInfo('integro_operation');
    $this->setCacheBackend($cache_backend, 'integro_operation', ['integro_operation']);
  }

}
