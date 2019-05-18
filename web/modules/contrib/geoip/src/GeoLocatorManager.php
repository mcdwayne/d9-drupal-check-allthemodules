<?php

namespace Drupal\geoip;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for geolocator plugins.
 */
class GeoLocatorManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    'weight' => 0,
    'description' => '',
  ];

  /**
   * The plugin instances.
   *
   * @var array
   */
  protected $instances = [];

  /**
   * Constructs a new \Drupal\geoip\GeoLocatorsManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(\Traversable $namespaces, ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $interface = 'Drupal\geoip\Plugin\GeoLocator\GeoLocatorInterface';
    $annotation = 'Drupal\geoip\Annotation\GeoLocator';

    parent::__construct('Plugin/GeoLocator', $namespaces, $module_handler, $interface, $annotation);
    $this->alterInfo('geolocator');
    $this->setCacheBackend($cache_backend, 'geolocator_plugins', ['geoip']);
  }

}
