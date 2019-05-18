<?php

namespace Drupal\gtm_datalayer\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides a plugin manager for GTM dataLayer Processor plugins.
 */
class DataLayerProcessorPluginManager extends DefaultPluginManager implements DataLayerProcessorPluginManagerInterface {

  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/DataLayerProcessor', $namespaces, $module_handler, 'Drupal\gtm_datalayer\Plugin\DataLayerProcessorInterface', 'Drupal\gtm_datalayer\Annotation\DataLayerProcessor');

    $this->alterInfo('gtm_datalayer_info');
    $this->setCacheBackend($cache_backend, 'gtm_datalayer');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $instances = $this->createInstances([$plugin_id], $configuration);

    return reset($instances);
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances($plugin_id = [], array $configuration = []) {
    if (empty($plugin_id)) {
      $plugin_id = array_keys($this->getDefinitions());
    }

    $factory = $this->getFactory();
    $plugin_ids = (array) $plugin_id;

    $instances = [];
    foreach ($plugin_ids as $plugin_id) {
      $instances[$plugin_id] = $factory->createInstance($plugin_id, isset($configuration[$plugin_id]) ? $configuration[$plugin_id] : []);
    }

    return $instances;
  }

}
