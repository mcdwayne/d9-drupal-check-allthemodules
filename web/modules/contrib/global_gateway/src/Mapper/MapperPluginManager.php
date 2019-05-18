<?php

namespace Drupal\global_gateway\Mapper;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages mapper plugins.
 *
 * @see \Drupal\global_gateway\Annotation\GlobalGatewayMapper
 * @see \Drupal\global_gateway\Mapper\MapperInterface
 * @see \Drupal\global_gateway\Mapper\MapperPluginBase
 * @see plugin_api
 */
class MapperPluginManager extends DefaultPluginManager {

  /**
   * Static cache for the data type definitions.
   *
   * @var \Drupal\global_gateway\Mapper\MapperInterface[]
   *
   * @see \Drupal\global_gateway\Mapper\MapperPluginManager::createInstance()
   * @see \Drupal\global_gateway\Mapper\MapperPluginManager::getInstances()
   */
  protected $mappers;

  /**
   * Whether all plugin instances have already been created.
   *
   * @var bool
   *
   * @see \Drupal\global_gateway\Mapper\MapperPluginManager::getInstances()
   */
  protected $allCreated = FALSE;

  /**
   * Constructs a MapperPluginManager object.
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
    parent::__construct('Plugin/global_gateway/mapper', $namespaces, $module_handler, 'Drupal\global_gateway\Mapper\MapperInterface', 'Drupal\global_gateway\Annotation\GlobalGatewayMapper');

    $this->setCacheBackend($cache_backend, 'global_gateway_mapper');
    $this->alterInfo('global_gateway_mapper_info');
  }

  /**
   * Creates or retrieves a data type plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   (optional) An array of configuration relevant to the plugin instance.
   *   Ignored for data type plugins.
   *
   * @return \Drupal\global_gateway\Mapper\MapperInterface
   *   The requested data type plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (empty($this->mappers[$plugin_id])) {
      $this->mappers[$plugin_id] = parent::createInstance($plugin_id, $configuration);
    }
    return $this->mappers[$plugin_id];
  }

  /**
   * Returns all known data types.
   *
   * @return \Drupal\global_gateway\Mapper\MapperInterface[]
   *   An array of data type plugins, keyed by type identifier.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getInstances() {
    if (!$this->allCreated) {
      $this->allCreated = TRUE;
      if (!isset($this->mappers)) {
        $this->mappers = [];
      }

      foreach ($this->getDefinitions() as $plugin_id => $definition) {
        if (class_exists($definition['class']) && empty($this->mappers[$plugin_id])) {
          $mapper = $this->createInstance($plugin_id);
          $this->mappers[$plugin_id] = $mapper;
        }
      }
    }

    return $this->mappers;
  }

  /**
   * Returns all field data types known by the Search API as an options list.
   *
   * @return string[]
   *   An associative array with all recognized types as keys, mapped to their
   *   translated display names.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see \Drupal\global_gateway\Mapper\MapperPluginManager::getInstances()
   */
  public function getInstancesOptions() {
    $types = [];
    foreach ($this->getInstances() as $id => $info) {
      $types[$id] = $info->label();
    }
    return $types;
  }

}
