<?php

namespace Drupal\analytics\AnalyticsService;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for analytics items.
 *
 * @see \Drupal\analytics\Annotation\AnalyticsService
 * @see \Drupal\analytics\AnalyticsService\AnalyticsServiceBase
 * @see \Drupal\analytics\AnalyticsService\AnalyticsServiceInterface
 * @see plugin_api
 */
class AnalyticsServiceManager extends DefaultPluginManager {

  /**
   * Constructs a new AnalyticsServiceManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/AnalyticsService', $namespaces, $module_handler, 'Drupal\analytics\AnalyticsService\AnalyticsServiceInterface', 'Drupal\analytics\Annotation\AnalyticsService'); 
    $this->alterInfo('analytics_service_plugins');
    $this->setCacheBackend($cache_backend, 'analytics_service_plugins');
  }
  
  /**
   * Provides a list of plugins suitable for form options.
   *
   * @return array
   *   An array of valid plugin labels, keyed by plugin ID.
   */
  public function getDefinitionOptions() {
    $options = array_map(function ($definition) {
      return (string) $definition['label'];
    }, $this->getDefinitions());
    natsort($options);
    return $options;
  }
}
