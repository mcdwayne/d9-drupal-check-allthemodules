<?php

namespace Drupal\geoip;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Service to interact with the default geolocator plugin for geolocation.
 */
class GeoLocation {

  use UseCacheBackendTrait;

  /**
   * Plugin manager for GeoLocator plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $geoLocatorManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $cacheKey = 'geolocated_ips';
  protected $cacheTags = ['geoip'];
  protected $locatedAddresses = [];
  protected $config = [];

  /**
   * Constructs a new GeoLocation object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $geolocators_manager
   *   The geolocation locator plugin manager service to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(PluginManagerInterface $geolocators_manager, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    $this->geoLocatorManager = $geolocators_manager;
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cache_backend;
    $this->config = $this->configFactory->get('geoip.geolocation');
  }

  /**
   * Gets the identifier of the default geolocator plugin.
   *
   * @return string
   *   Identifier of the default geolocator plugin.
   */
  public function getGeoLocatorId() {
    return $this->config->get('plugin_id');
  }

  /**
   * Gets an instance of the default geolocator plugin.
   *
   * @return \Drupal\geoip\Plugin\GeoLocator\GeoLocatorInterface
   *   Instance of the default geolocator plugin.
   */
  public function getGeoLocator() {
    return $this->geoLocatorManager->createInstance($this->config->get('plugin_id'));
  }

  /**
   * Geolocate an IP address.
   *
   * @param string $ip_address
   *   The IP address to geo locate.
   *
   * @return string|null
   *   The geolocated country code, or NULL if not found.
   */
  public function geolocate($ip_address) {
    if (!isset($this->locatedAddresses[$ip_address])) {
      if ($cache = $this->cacheBackend->get($this->cacheKey . ':' . $ip_address)) {
        $this->locatedAddresses[$ip_address] = $cache->data;
      }
      else {
        $geolocator = $this->getGeoLocator();

        $result = $geolocator->geolocate($ip_address);
        $this->locatedAddresses[$ip_address] = $result;
        $this->cacheBackend->set($this->cacheKey . ':' . $ip_address, $this->locatedAddresses[$ip_address], Cache::PERMANENT, $this->cacheTags);
      }
    }

    return $this->locatedAddresses[$ip_address];
  }

}
