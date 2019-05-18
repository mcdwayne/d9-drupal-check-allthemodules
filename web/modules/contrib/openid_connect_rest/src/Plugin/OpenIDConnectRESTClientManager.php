<?php

namespace Drupal\openid_connect_rest\Plugin;

use Traversable;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;

/**
 * Class OpenIDConnectRESTClientManager.
 *
 * Provides the OpenID Connect REST client plugin manager.
 *
 * @package Drupal\openid_connect_rest\Plugin
 */
class OpenIDConnectRESTClientManager extends OpenIDConnectClientManager {

  /**
   * Overrides OpenIDConnectClientManager Constructor.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
      Traversable $namespaces,
      CacheBackendInterface $cache_backend,
      ModuleHandlerInterface $module_handler
  ) {
    DefaultPluginManager::__construct(
      'Plugin/OpenIDConnectRESTClient',
      $namespaces,
      $module_handler,
      'Drupal\openid_connect\Plugin\OpenIDConnectClientInterface',
      'Drupal\openid_connect\Annotation\OpenIDConnectClient'
    );

    $this->alterInfo('openid_connect_openid_connect_rest_client_info');
    $this->setCacheBackend(
      $cache_backend,
      'openid_connect_openid_connect_rest_client_plugins'
    );
  }

}
