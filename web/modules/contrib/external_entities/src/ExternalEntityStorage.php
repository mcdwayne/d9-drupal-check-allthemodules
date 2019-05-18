<?php

namespace Drupal\external_entities;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\external_entities\Event\ExternalEntitiesEvents;
use Drupal\external_entities\Event\ExternalEntityMapRawDataEvent;

/**
 * Defines the storage handler class for external entities.
 *
 * This extends the base storage class, adding required special handling for
 * e entities.
 */
class ExternalEntityStorage extends ContentEntityStorageBase implements ExternalEntityStorageInterface {

  /**
   * The external storage client manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageClientManager;

  /**
   * Storage client instance.
   *
   * @var \Drupal\external_entities\StorageClient\ExternalEntityStorageClientInterface
   */
  protected $storageClient;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('entity.memory_cache'),
      $container->get('plugin.manager.external_entities.storage_client'),
      $container->get('datetime.time'),
      $container->get('entity_field.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Constructs a new ExternalEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $storage_client_manager
   *   The storage client manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityManagerInterface $entity_manager,
    CacheBackendInterface $cache,
    MemoryCacheInterface $memory_cache,
    PluginManagerInterface $storage_client_manager,
    TimeInterface $time,
    EntityFieldManagerInterface $entity_field_manager,
    EventDispatcherInterface $event_dispatcher
  ) {
    parent::__construct($entity_type, $entity_manager, $cache, $memory_cache);
    $this->storageClientManager = $storage_client_manager;
    $this->time = $time;
    $this->entityFieldManager = $entity_field_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClient() {
    if (!$this->storageClient) {
      $this->storageClient = $this
        ->getExternalEntityType()
        ->getStorageClient();
    }
    return $this->storageClient;
  }

  /**
   * Acts on entities before they are deleted and before hooks are invoked.
   *
   * Used before the entities are deleted and before invoking the delete hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   *
   * @throws EntityStorageException
   */
  public function preDelete(array $entities) {
    if ($this->getExternalEntityType()->isReadOnly()) {
      throw new EntityStorageException($this->t('Can not delete read-only external entities.'));
    }
  }

  /**
   * Gets the entity type definition.
   *
   * @return \Drupal\external_entities\ExternalEntityTypeInterface
   *   Entity type definition.
   */
  public function getEntityType() {
    /* @var \Drupal\external_entities\ExternalEntityTypeInterface $entity_type */
    $entity_type = $this->entityType;
    return $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    // Do the actual delete.
    foreach ($entities as $entity) {
      $this->getStorageClient()->delete($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    // Attempt to load entities from the persistent cache. This will remove IDs
    // that were loaded from $ids.
    $entities_from_cache = $this->getFromPersistentCache($ids);

    // Load any remaining entities from the external storage.
    if ($entities_from_storage = $this->getFromExternalStorage($ids)) {
      $this->invokeStorageLoadHook($entities_from_storage);
      $this->setPersistentCache($entities_from_storage);
    }

    $entities = $entities_from_cache + $entities_from_storage;

    // Map annotation fields to annotatable external entities.
    foreach ($entities as $external_entity) {
      /* @var \Drupal\external_entities\ExternalEntityInterface $external_entity */
      if ($external_entity->getExternalEntityType()->isAnnotatable()) {
        $external_entity->mapAnnotationFields();
      }
    }

    return $entities;
  }

  /**
   * Gets entities from the external storage.
   *
   * @param array|null $ids
   *   If not empty, return entities that match these IDs. Return no entities
   *   when NULL.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Array of entities from the storage.
   */
  protected function getFromExternalStorage(array $ids) {
    $entities = [];

    if (!empty($ids)) {
      // Sanitize IDs. Before feeding ID array into buildQuery, check whether
      // it is empty as this would load all entities.
      $ids = $this->cleanIds($ids);
    }

    if ($ids) {
      $data = $this
        ->getStorageClient()
        ->loadMultiple($ids);

      // Map the data into entity objects and according fields.
      if ($data) {
        $entities = $this->mapFromRawStorageData($data);
      }
    }

    return $entities;
  }

  /**
   * Maps from storage data to entity objects, and attaches fields.
   *
   * @param array $data
   *   Associative array of storage results, keyed on the entity ID.
   *
   * @return \Drupal\external_entities\ExternalEntityInterface[]
   *   An array of entity objects implementing the ExternalEntityInterface.
   */
  protected function mapFromRawStorageData(array $data) {
    if (!$data) {
      return [];
    }

    $field_definitions = $this
      ->entityFieldManager
      ->getFieldDefinitions($this->getEntityTypeId(), $this->getEntityTypeId());
    $values = [];
    foreach ($data as $id => $raw_data) {
      $values[$id] = [];

      foreach ($this->getExternalEntityType()->getFieldMappings() as $field_name => $properties) {
        $field_definition = $field_definitions[$field_name];
        $field_values = [];

        foreach ($properties as $property_name => $mapped_key) {
          // The plus (+) character at the beginning of a mapping key indicates
          // the property doesn't have a mapping but a default value. We process
          // default values after all the regular mappings have been processed.
          if (strpos($mapped_key, '+') === 0) {
            continue;
          }

          $exploded_mapped_key = explode('/', $mapped_key);
          // The asterisk (*) character indicates that we are dealing with a
          // multivalued field. We consider each individual field item to be in
          // its separate array.
          if (!empty($raw_data[$exploded_mapped_key[0]]) && isset($exploded_mapped_key[1]) && $exploded_mapped_key[1] === '*') {
            $parents = array_slice($exploded_mapped_key, 2);
            foreach (array_values($raw_data[$exploded_mapped_key[0]]) as $key => $value) {
              $value = !is_array($value) ? [$value] : $value;
              $field_values[$key][$property_name] = NestedArray::getValue($value, $parents);
            }
          }
          else {
            $property_values = NestedArray::getValue($raw_data, $exploded_mapped_key);
            if (!is_array($property_values)) {
              $property_values = [$property_values];
            }

            foreach (array_values($property_values) as $key => $property_value) {
              $field_values[$key][$property_name] = $property_value;
            }
          }
        }

        // Process the default values.
        foreach ($properties as $property_name => $mapped_key) {
          if (strpos($mapped_key, '+') === 0) {
            foreach (array_keys($field_values) as $key) {
              $field_values[$key][$property_name] = substr($mapped_key, 1);
            }
          }
        }

        // Provide specific conversion for dates.
        $date_fields = ['created', 'changed', 'datetime', 'timestamp'];
        if (in_array($field_definition->getType(), $date_fields) && $property_name === 'value') {
          foreach ($field_values as $key => $item) {
            if (!empty($item['value'])) {
              $timestamp = !is_numeric($item['value'])
                ? strtotime($item['value'])
                : $item['value'];

              if ($field_definition->getType() === 'datetime') {
                switch ($field_definition->getSetting('datetime_type')) {
                  case DateTimeItem::DATETIME_TYPE_DATE:
                    $format = DateTimeItemInterface::DATE_STORAGE_FORMAT;
                    break;

                  default:
                    $format = DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
                    break;
                }
                $item['value'] = \Drupal::service('date.formatter')->format($timestamp, 'custom', $format);
              }
              else {
                $item['value'] = $timestamp;
              }

              $field_values[$key] = $item;
            }
            else {
              unset($field_values[$key]);
            }
          }
        }

        if (!empty($field_values)) {
          $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT] = $field_values;
        }
      }
    }

    $entities = [];
    foreach ($values as $id => $entity_values) {
      // Allow other modules to perform custom mapping logic.
      $event = new ExternalEntityMapRawDataEvent($entity_values);
      $this->eventDispatcher->dispatch(ExternalEntitiesEvents::MAP_RAW_DATA, $event);

      $entities[$id] = new $this->entityClass($event->getRawData(), $this->entityTypeId);
      $entities[$id]->enforceIsNew(FALSE);
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function setPersistentCache($entities) {
    if (!$this->entityType->isPersistentlyCacheable()) {
      return;
    }

    $cache_tags = [
      $this->entityTypeId . '_values',
      'entity_field_info',
    ];

    foreach ($entities as $id => $entity) {
      $max_age = $this->getExternalEntityType()->getPersistentCacheMaxAge();
      $expire = $max_age === Cache::PERMANENT
        ? Cache::PERMANENT
        : $this->time->getRequestTime() + $max_age;
      $this->cacheBackend->set($this->buildCacheId($id), $entity, $expire, $cache_tags);
    }
  }

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @throws EntityStorageException
   */
  public function preSave(EntityInterface $entity) {
    $external_entity_type = $this->getExternalEntityType();
    if ($external_entity_type->isReadOnly() && !$external_entity_type->isAnnotatable()) {
      throw new EntityStorageException($this->t('Can not save read-only external entities.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /* @var \Drupal\external_entities\ExternalEntityInterface $entity */
    $result = FALSE;

    $external_entity_type = $this->getExternalEntityType();
    if (!$external_entity_type->isReadOnly()) {
      $result = parent::doSave($id, $entity);
    }

    if ($external_entity_type->isAnnotatable()) {
      $referenced_entities = $entity
        ->get(ExternalEntityInterface::ANNOTATION_FIELD)
        ->referencedEntities();
      if ($referenced_entities) {
        $annotation = array_shift($referenced_entities);
        $annotation->set($external_entity_type->getAnnotationFieldName(), $entity->id());
        $annotation->save();
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.external';
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    $id = $this->getStorageClient()->save($entity);
    if ($id && $entity->isNew()) {
      $entity->{$this->idKey} = $id;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    return $as_bool ? 0 : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasData() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getExternalEntityType() {
    return $this->entityManager
      ->getStorage('external_entity_type')
      ->load($this->getEntityTypeId());
  }

}
