<?php

namespace Drupal\gapi\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use \Google_Client;

/**
 * Provides a Google API Service plugin manager.
 *
 * @see \Drupal\gapi\GoogleApiServiceProviderManager
 * @see plugin_api
 */
class GoogleApiServiceProviderManager extends DefaultPluginManager implements GoogleApiServiceProviderManagerInterface {

  /**
   * The Google_Client to use with the service provider.
   *
   * @var \Google_Client
   */
  protected $client;

  /**
   * Constructs a GoogleApiServiceProviderManager instance.
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
    parent::__construct(
      'Plugin/gapi/ServiceProvider',
      $namespaces,
      $module_handler,
      'Drupal\gapi\GoogleApiServiceProviderInterface',
      'Drupal\gapi\Annotation\GoogleApiServiceProvider'
    );
    $this->alterInfo('gapi_service_provider_info');
    $this->setCacheBackend($cache_backend, 'gapi_service_provider_info_plugins');
    $this->factory = new ContainerFactory($this->getDiscovery());
  }

  /**
   * {@inheritdoc}
   */
  public function setClient(Google_Client $client) {
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function getService($service_id, array $configuration = []) {
    assert(!is_null($this->client), 'A client must be set on the service provider manager.');

    /* @var \Drupal\gapi\Plugin\GoogleApiServiceProviderInterface $service_provider */
    $service_provider = $this->createInstance($service_id, $configuration);

    return $service_provider->getService($this->client);
  }

}
