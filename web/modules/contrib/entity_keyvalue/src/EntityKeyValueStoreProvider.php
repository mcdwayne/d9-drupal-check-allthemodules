<?php

namespace Drupal\entity_keyvalue;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\entity_keyvalue\EntityKeyValueStore\EntityKeyValueStoreInterface;

/**
 * Class EntityKeyValueStoreProvider.
 *
 * @package Drupal\entity_keyvalue
 */
class EntityKeyValueStoreProvider {

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The key-value storage.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * Maps entities with their services.
   *
   * @var array $entityStoreConfig.
   */
  protected $entityStoreConfig;

  /**
   * Default entity key-value store configuration.
   *
   * @var array $defaultEntityStoreConfig.
   */
  protected $defaultEntityStoreConfig = [
    'keys' => [],
    'autoload' => FALSE,
    'autodelete' => TRUE,
    'service' => 'entity_keyvalue_store_default',
  ];

  /**
   * EntityKeyValueStoreProvider constructor.
   *
   * @param ModuleHandler $module_handler
   *   Module handler object.
   * @param KeyValueFactoryInterface $key_value_factory
   *   KeyValue factory object.
   */
  public function __construct(ModuleHandler $module_handler, KeyValueFactoryInterface $key_value_factory) {
    $this->moduleHandler = $module_handler;
    $this->keyValueStore = $key_value_factory->get('entity_keyvalue');
    $this->entityStoreConfig = [];
    foreach ($this->moduleHandler->invokeAll('entity_keyvalue_info') as $entity_type_id => $config) {
      $this->entityStoreConfig[$entity_type_id] = array_merge($this->defaultEntityStoreConfig, $config);
    }
  }

  /**
   * Gets configuration for the entity-key-value store.
   *
   * @param string $entity_type_id
   *   Type of the entity to operate with its key:value data.
   *
   * @return array
   *   Configuration for the entity key:value storage.
   */
  public function getEntityKeyValueConfig($entity_type_id) {
    return $this->entityStoreConfig[$entity_type_id];
  }

  /**
   * Check if entity has related key-value store.
   *
   * @param string $entity_type_id
   *   Type of the entity to operate with its key:value data.
   *
   * @return bool
   *   TRUE if entity has related store, FALSE otherwise.
   */
  public function checkEntityStore($entity_type_id) {
    return isset($this->entityStoreConfig[$entity_type_id]);
  }

  /**
   * Gets entityKeyValue store.
   *
   * @param string $entity_type_id
   *   Type of the entity to operate with its key:value data.
   *
   * @return EntityKeyValueStoreInterface
   *   Entity key:value store.
   *
   * @throws \RuntimeException
   */
  public function getEntityStore($entity_type_id) {
    if (!$this->checkEntityStore($entity_type_id)) {
      throw new \RuntimeException('There are no entity_keyvalue stores for entity type: ' . $entity_type_id);
    }

    $service = \Drupal::service($this->entityStoreConfig[$entity_type_id]['service']);
    if (!$service instanceof EntityKeyValueStoreInterface) {
      throw new \RuntimeException('Entity type ' . $entity_type_id . ' related with incorrect EntityKeyValueStore');
    }

    return $service;
  }

}
