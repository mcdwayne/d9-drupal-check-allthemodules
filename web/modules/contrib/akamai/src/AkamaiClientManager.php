<?php

namespace Drupal\akamai;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages Akamai Client version plugins.
 *
 * @see \Drupal\akamai\Annotation\AkamaiClient
 * @see \Drupal\akamai\AkamaiClientInterface
 * @see \Drupal\akamai\AkamaiClientBase
 * @see plugin_api
 */
class AkamaiClientManager extends DefaultPluginManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the AkamaiClientManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct('Plugin/Client', $namespaces, $module_handler, 'Drupal\akamai\AkamaiClientInterface', 'Drupal\akamai\Annotation\AkamaiClient');

    $this->alterInfo('akamai_client_plugins');
    $this->setCacheBackend($cache_backend, 'akamai_client_plugins');
    $this->configFactory = $config_factory;
  }

  /**
   * Gets the default Akamai client version.
   *
   * @return string|bool
   *   Version of the default client, or FALSE on error.
   */
  public function getDefaultClientVersion() {
    $client_version = $this->configFactory->get('akamai.settings')->get('version');
    $clients = $this->getAvailableVersions();

    if (!isset($clients[$client_version]) || !class_exists($clients[$client_version]['class'])) {
      // The selected client isn't available so return the first one found. If
      // none are available this will return FALSE.
      reset($clients);
      $client_version = key($clients);
    }

    return $client_version;
  }

  /**
   * Gets a list of available clients.
   *
   * @return array
   *   An array with the version names as keys and the descriptions as values.
   */
  public function getAvailableVersions() {
    // Use plugin system to get list of available clients.
    $versions = $this->getDefinitions();

    $output = [];
    foreach ($versions as $id => $definition) {
      $output[$id] = $definition;
    }

    return $output;
  }

}
