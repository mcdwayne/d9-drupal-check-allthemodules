<?php

namespace Drupal\geocoder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\geocoder\Annotation\GeocoderProvider;

/**
 * Provides a plugin manager for geocoder providers.
 */
class ProviderPluginManager extends GeocoderPluginManagerBase {

  /**
   * Constructs a new geocoder provider plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct('Plugin/Geocoder/Provider', $namespaces, $module_handler, ProviderInterface::class, GeocoderProvider::class);
    $this->alterInfo('geocoder_provider_info');
    $this->setCacheBackend($cache_backend, 'geocoder_provider_plugins');
  }

  /**
   * Returns the defined plugins.
   *
   * Note that this method has been changed in Geocoder 3.x. It currently
   * returns the list of plugin definitions which is identical to the list
   * returned by ::getDefinitions().
   *
   * In Geocoder 2.x this was returning a mix of plugin definitions and
   * configured providers but this architecture has been replaced by the new
   * GeocoderProvider config entity.
   *
   * It is recommended to no longer use this method but instead use one of these
   * two alternatives:
   *
   * In order to get a list of all available plugin definitions:
   * @code
   * $definitions = \Drupal\geocoder\ProviderPluginManager::getDefinitions();
   * @endcode
   *
   * In order to get a list of all geocoding providers that are configured by
   * the site builder:
   * @code
   * $providers = \Drupal\geocoder\Entity\GeocoderProvider::loadMultiple();
   * @endcode
   *
   * @return array
   *   A list of plugins.
   */
  public function getPlugins(): array {
    return $this->getDefinitions();
  }

}
