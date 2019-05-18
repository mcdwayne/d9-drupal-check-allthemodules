<?php

namespace Drupal\eloqua_app_cloud\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Eloqua AppCloud Menu Responder plugin manager.
 */
class EloquaAppCloudMenuResponderManager extends DefaultPluginManager {

  /**
   * Constructor for EloquaAppCloudMenuResponderManager objects.
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
    parent::__construct('Plugin/EloquaAppCloudMenuResponder', $namespaces, $module_handler, 'Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudMenuResponderInterface', 'Drupal\eloqua_app_cloud\Annotation\EloquaAppCloudMenuResponder');

    $this->alterInfo('eloqua_app_cloud_eloqua_app_cloud_menu_responder_info');
    $this->setCacheBackend($cache_backend, 'eloqua_app_cloud_eloqua_app_cloud_menu_responder_plugins');
  }

}
