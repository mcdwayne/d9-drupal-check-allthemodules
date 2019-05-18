<?php

namespace Drupal\environmental_config;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a EnvironmentDetector plugin manager.
 *
 * @see environmental_config
 */
class EnvironmentDetectorManager extends DefaultPluginManager {

  /**
   * EnvironmentDetectorManager constructor.
   *
   * @param \Traversable $namespaces
   *   The namespaces.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache_backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module_handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/EnvironmentDetector',
      $namespaces,
      $module_handler,
      'Drupal\\environmental_config\\EnvironmentDetectorInterface',
      'Drupal\\environmental_config\\Annotation\\EnvironmentDetector'
    );
    $this->alterInfo('environmental_config_environmentdetector_info');
    $this->setCacheBackend($cache_backend, 'environmental_config_environmentdetector_info_plugins');
  }

}
