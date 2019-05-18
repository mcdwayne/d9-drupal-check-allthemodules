<?php

namespace Drupal\global_gateway;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages region negotiation methods.
 */
class RegionNegotiationTypeManager extends DefaultPluginManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new RegionNegotiationTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   An object that implements CacheBackendInterface.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   An object that implements ModuleHandlerInterface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   An object that implements ConfigFactoryInterface.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/RegionNegotiation', $namespaces, $module_handler, 'Drupal\global_gateway\RegionNegotiationTypeInterface', 'Drupal\global_gateway\Annotation\RegionNegotiation');
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cache_backend;
    $this->cacheKeyPrefix = 'global_gateway_region_negotiation_type_plugins';
    $this->cacheKey = 'global_gateway_region_negotiation_type_plugins';
    $this->alterInfo('global_gateway_region_negotiation_type_info');
  }

  public function getInstance(array $options) {
    $plugin_id = $options['id'];

    if (!isset($options['config'])) {
      $options['config'] = [];
    }

    if (empty($this->instances[$plugin_id])) {
      $this->instances[$plugin_id] = $this->createInstance($plugin_id, $options['config']);
    }
    return $this->instances[$plugin_id];
  }

}
