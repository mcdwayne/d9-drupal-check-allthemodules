<?php

namespace Drupal\oauth2_client\PluginManager;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Traversable;

/**
 * The OAuth 2 Client plugin manager.
 */
class Oauth2ClientPluginManager extends DefaultPluginManager implements Oauth2ClientPluginManagerInterface {

  protected $settings;

  /**
   * Constructs an Oauth2ClientPluginManager object.
   *
   * @param \Traversable $namespaces
   *   Namespaces to be searched for the plugin.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cacheBackend, ModuleHandlerInterface $moduleHandler) {
    parent::__construct('Plugin/Oauth2Client', $namespaces, $moduleHandler, 'Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginInterface', 'Drupal\oauth2_client\Annotation\Oauth2Client');

    $this->alterInfo('oauth2_client_info');
    $this->setCacheBackend($cacheBackend, 'oauth2_client');
  }

}
