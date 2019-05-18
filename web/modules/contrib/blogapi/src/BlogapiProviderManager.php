<?php

namespace Drupal\blogapi;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class BlogapiProvider.
 *
 * @package Drupal\blogapi\Provider
 */
class BlogapiProviderManager extends DefaultPluginManager {

  /**
   * BlogapiProviderManager constructor.
   *
   * @param \Traversable $namespaces
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $subdir = 'Plugin/BlogapiProvider';
    $plugin_anotation = 'Drupal\blogapi\Annotation\Provider';

    parent::__construct($subdir, $namespaces, $module_handler, 'Drupal\blogapi\BlogapiProviderInterface', $plugin_anotation);
    $this->alterInfo('blogapi_providers_info');
    $this->setCacheBackend($cache_backend, 'blogapi_providers');
  }

}
