<?php

namespace Drupal\entity_sanitizer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a Field Sanitizer plugin manager
 *
 * @see plugin_api
 */
class FieldSanitizerManager extends DefaultPluginManager {

  /**
   * Constructs a FieldSanitizerManager object.
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
      'Plugin/FieldSanitizer',
      $namespaces,
      $module_handler,
      'Drupal\entity_sanitizer\FieldSanitizerInterface',
      'Drupal\entity_sanitizer\Annotation\FieldSanitizer'
    );

    $this->alterInfo('field_sanitizer_info');
    $this->setCacheBackend($cache_backend, 'field_sanitizer_info_plugins');
  }

}