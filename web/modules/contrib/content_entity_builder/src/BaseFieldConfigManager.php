<?php

namespace Drupal\content_entity_builder;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages tab plugins.
 *
 * @see hook_base_field_config_info_alter()
 * @see \Drupal\content_entity_builder\Annotation\BaseFieldConfig
 * @see \Drupal\content_entity_builder\BaseFieldConfigInterface
 * @see \Drupal\content_entity_builder\BaseFieldConfigBase
 * @see plugin_api
 */
class BaseFieldConfigManager extends DefaultPluginManager {

  /**
   * Constructs a new  BaseFieldConfigManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/BaseFieldConfig', $namespaces, $module_handler, 'Drupal\content_entity_builder\BaseFieldConfigInterface', 'Drupal\content_entity_builder\Annotation\BaseFieldConfig');

    $this->alterInfo('base_field_config_info');
    $this->setCacheBackend($cache_backend, 'base_field_config_plugins');
  }

}
