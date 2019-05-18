<?php

namespace Drupal\geocoder\Plugin\Geocoder\Provider;

use Drupal\geocoder\ConfigurableProviderUsingHandlerWithAdapterBase;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Http\Client\HttpClient;

/**
 * Geocoder provider plugin for Google Maps for Business.
 *
 * @GeocoderProvider(
 *   id = "googlemaps_business",
 *   name = "GoogleMapsBusiness",
 *   handler = "\Geocoder\Provider\GoogleMaps\GoogleMaps",
 *   arguments = {
 *     "clientId" = "",
 *     "privateKey" = "",
 *     "region" = "",
 *     "apiKey" = "",
 *     "channel" = ""
 *   }
 * )
 */
class GoogleMapsBusiness extends ConfigurableProviderUsingHandlerWithAdapterBase {

  /**
   * GoogleMapsBusiness constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend used to cache geocoding data.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The Drupal language manager service.
   * @param \Http\Client\HttpClient $http_adapter
   *   The HTTP adapter.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @throws \Exception
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager, HttpClient $http_adapter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $cache_backend, $language_manager, $http_adapter);
    throw new \Exception('Google Maps for Business needs to be instantiated by calling \Geocoder\Provider\GoogleMaps\GoogleMaps::business()');
  }

}
