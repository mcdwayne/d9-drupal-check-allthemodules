<?php

namespace Drupal\libraries_provider;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Library Source plugin manager.
 */
class LibrarySourcePluginManager extends DefaultPluginManager {

  /**
   * Constructs a LibrarySourcePluginManager object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/LibrarySource',
      $namespaces,
      $module_handler,
      'Drupal\libraries_provider\Plugin\LibrarySource\LibrarySourceInterface',
      'Drupal\libraries_provider\Annotation\LibrarySource'
    );

    $this->setCacheBackend($cache_backend, 'libraries_provider_sources');
    $this->alterInfo('libraries_provider_sources_info');
  }

  /**
   * Create an instance of all available plugins.
   */
  public function createAllInstances() {
    $instances = [];
    foreach (array_keys($this->getDefinitions()) as $pluginId) {
      $instances[$pluginId] = $this->createInstance($pluginId);
    }
    return $instances;

  }

}
