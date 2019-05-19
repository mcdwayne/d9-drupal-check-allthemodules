<?php

namespace Drupal\toolshed;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a plugin manager for managing menu resolvers.
 *
 * @see \Drupal\toolshed\Annotation\ThirdPartyFormElements
 * @see \Drupal\toolshed\ThirdPartyFormElementsInterface
 * @see plugin_api
 */
class ThirdPartyFormElementsPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ThirdPartyFormElementsPluginManager object.
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
      'Plugin/Toolshed/ThirdPartyFormElements',
      $namespaces,
      $module_handler,
      'Drupal\toolshed\ThirdPartyFormElementsInterface',
      'Drupal\toolshed\Annotation\ThirdPartyFormElements'
    );
  }

  /**
   * Get an array of plugin definitions that apply to the passed in $entity.
   *
   * @return array[]
   *   An array of plugin definitions.
   */
  public function getSettingsFormPlugins(ConfigEntityInterface $entity) {
    $plugins = [];
    $entityType = $entity->getEntityTypeId();

    foreach ($this->getDefinitions() as $pluginDef) {
      if (in_array($entityType, $pluginDef['entity_types'])) {
        $plugins[] = $pluginDef;
      }
    }

    return $plugins;
  }

}
