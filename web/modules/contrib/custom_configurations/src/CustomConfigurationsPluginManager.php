<?php

namespace Drupal\custom_configurations;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the custom configurations plugin manager.
 *
 * @see plugin_api
 */
class CustomConfigurationsPluginManager extends DefaultPluginManager {

  /**
   * Constructs a GeSettingsPluginManager object.
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

    // Will seek plugins in the GeSettings golder of the src folder.
    $subdir = 'Plugin/CustomConfigurations';
    $interface = 'Drupal\custom_configurations\CustomConfigurationsPluginInterface';
    $annotation = 'Drupal\Component\Annotation\Plugin';

    parent::__construct($subdir, $namespaces, $module_handler, $interface, $annotation);

    // This allows the plugin definitions to be altered by an alter hook.
    $this->alterInfo('custom_configurations_info');

    // This sets the caching method for our plugin definitions.
    $this->setCacheBackend($cache_backend, 'custom_configurations_info');
  }

}
