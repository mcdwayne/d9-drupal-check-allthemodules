<?php
/**
 * @file
 * Contains Drupal\schema\SchemaManager.
 */

namespace Drupal\schema;


use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manager for SchemaProvider plugins.
 */
class SchemaManager extends DefaultPluginManager {
  /**
   * Constructs a new \Drupal\Core\Block\BlockManager object.
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
    parent::__construct('Plugin/Schema', $namespaces, $module_handler, 'Drupal\schema\SchemaProviderInterface', 'Drupal\schema\SchemaProvider');

    $this->alterInfo('schema_provider');
    $this->setCacheBackend($cache_backend, 'schema_provider_plugins');
    $this->factory = new DefaultFactory($this, 'Drupal\schema\SchemaProviderInterface');
  }

  /**
   * Instantiates all plugins.
   *
   * @return array
   */
  public function createInstances() {
    $plugins = array();
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      $plugins[$plugin_id] = $this->createInstance($plugin_id);
    }
    return $plugins;
  }

}
