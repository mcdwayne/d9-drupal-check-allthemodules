<?php

namespace Drupal\migrate_social;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\migrate_social\Annotation\SocialNetwork;
use Drupal\plugin_type_example\Annotation\Sandwich;

/**
 * A plugin manager for related content plugins.
 */
class SocialNetworkPluginManager extends DefaultPluginManager {

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
    $plugin_interface = SocialNetworkInterface::class;

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = SocialNetwork::class;

    parent::__construct('Plugin/SocialNetwork', $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->alterInfo('social_network_info');
    $this->setCacheBackend($cache_backend, 'social_network_info');
  }

}
