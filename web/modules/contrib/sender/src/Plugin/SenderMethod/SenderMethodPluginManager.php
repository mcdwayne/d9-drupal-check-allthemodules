<?php

namespace Drupal\sender\Plugin\SenderMethod;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sender\Annotation\SenderMethod;

/**
 * Manages sender method plugin instantiation.
 */
class SenderMethodPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
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
    // Sender method plugins will reside under src/Plugin/SenderMethod folder.
    $subdir = 'Plugin/SenderMethod';

    // Sender method plugins must implement SenderMethodInterface.
    $plugin_interface = SenderMethodInterface::class;

    // The annotation name for sender method plugins.
    $annotation_name = SenderMethod::class;

    // Let the parent class take care of the rest.
    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $annotation_name);

    // Sets the hook alter.
    $this->alterInfo('sender_method_info');

    // Sets the caching method for plugin definitions.
    $this->setCacheBackend($cache_backend, 'sender_method_info');
  }

}
