<?php

namespace Drupal\plugindecorator;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;

/**
 * Manages Plugindecorator Plugins.
 */
class PluginDecoratorManager extends DefaultPluginManager {

  /**
   * Constructs a new PlugindecoratorManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PluginDecorator', $namespaces, $module_handler, NULL, 'Drupal\plugindecorator\Annotation\PluginDecorator');
    $this->setCacheBackend($cacheBackend, 'plugindecorator');
  }

}
