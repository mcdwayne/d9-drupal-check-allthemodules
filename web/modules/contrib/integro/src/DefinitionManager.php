<?php

namespace Drupal\integro;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\integro\Annotation\IntegroDefinition;

/**
 * Manages discovery and instantiation of plugins.
 */
class DefinitionManager extends DefaultPluginManager implements DefinitionManagerInterface {

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
    parent::__construct('Plugin/Integro/Definition', $namespaces, $module_handler, DefinitionInterface::class, IntegroDefinition::class);
    $this->alterInfo('integro_definition');
    $this->setCacheBackend($cache_backend, 'integro_definition', ['integro_definition']);
  }

}
