<?php

namespace Drupal\content_synchronizer\Processors\Entity;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * The entity processor plugin manager.
 */
class EntityProcessorPluginManager extends DefaultPluginManager {
  const SERVICE_NAME = 'plugin.manager.content_synchronizer.entity_processor';
  const PACKAGE_NAME = 'entity_processor';

  static private $instances = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/content_synchronizer/' . self::PACKAGE_NAME,
      $namespaces,
      $module_handler,
      'Drupal\content_synchronizer\Processors\Entity\EntityProcessorInterface',
      'Drupal\content_synchronizer\Annotation\EntityProcessor');

    $this->alterInfo('content_synchronizer_entity_processor_info');
    $this->setCacheBackend($cache_backend, 'content_synchronizer_entity_processor_info');
  }

  /**
   * Get the plugin by bundle type.
   *
   * @param string $entityType
   *   The entity type id.
   *
   * @return EntityProcessorBase
   *   The entity processor base.
   */
  public function getInstanceByEntityType($entityType) {
    $instance = NULL;
    foreach ($this->getDefinitions() as $pluginId => $definition) {
      if ($definition['entityType'] == $entityType) {
        $instance = static::createInstance($pluginId, []);
      }
    }
    if (is_null($instance)) {
      if (!array_key_exists('default', static::$instances)) {
        static::$instances['default'] = new EntityProcessorBase([], 'default', []);
      }
      $instance = static::$instances['default'];
    }
    $instance->setEntityType($entityType);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    if (!array_key_exists($plugin_id, static::$instances)) {
      static::$instances[$plugin_id] = parent::createInstance($plugin_id, $configuration);
    }
    return static::$instances[$plugin_id];
  }

}
