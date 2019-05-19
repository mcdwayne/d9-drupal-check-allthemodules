<?php

namespace Drupal\virtual_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldDefinitionInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VirtualEntityStorage.
 *
 * @package Drupal\virtual_entities\Storage
 */
class VirtualEntityStorage extends ContentEntityStorageBase {

  /**
   * The storage client manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $storageClientManager;

  /**
   * The decoder to decode the data.
   *
   * @var \Drupal\virtual_entities\VirtualEntityDecoderService
   */
  protected $decoder;

  /**
   * The HTTP client to fetch the data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, PluginManagerInterface $storage_client_manager, VirtualEntityDecoderServiceInterface $decoder, ClientInterface $http_client) {
    parent::__construct($entity_type, $entity_manager, $cache);

    $this->storageClientManager = $storage_client_manager;
    $this->decoder = $decoder;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('plugin.manager.virtual_entity.storage_client.plugin.processor'),
      $container->get('virtual_entity.storage_client.decoder'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function readFieldItemsToPurge(FieldDefinitionInterface $field_definition, $batch_size) {
    // TODO: Implement readFieldItemsToPurge() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function purgeFieldItems(ContentEntityInterface $entity, FieldDefinitionInterface $field_definition) {
    // TODO: Implement purgeFieldItems() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadRevisionFieldItems($revision_id) {
    // TODO: Implement doLoadRevisionFieldItems() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doSaveFieldItems(ContentEntityInterface $entity, array $names = []) {
    // TODO: Implement doSaveFieldItems() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteFieldItems($entities) {
    // TODO: Implement doDeleteFieldItems() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doDeleteRevisionFieldItems(ContentEntityInterface $revision) {
    // TODO: Implement doDeleteRevisionFieldItems() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    $id = $entity->id();

    // Track the original ID.
    if ($entity->getOriginalId() !== NULL) {
      $id = $entity->getOriginalId();
    }

    // Track if this entity exists already.
    $id_exists = $this->has($id, $entity);

    // A new entity should not already exist.
    if ($id_exists && $entity->isNew()) {
      throw new EntityStorageException("'{$this->entityTypeId}' entity with ID '$id' already exists.");
    }

    // Load the original entity, if any.
    if ($id_exists && !isset($entity->original)) {
      $entity->original = $this->loadUnchanged($id);
    }

    // Allow code to run before saving.
    $entity->preSave($this);
    $this->invokeHook('presave', $entity);

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    $entities = [];

    foreach ($ids as $id) {
      if (strpos($id, '-')) {
        list($bundle, $virtualId) = explode('-', $id, 2);
        if ($virtualId) {
          $clientLoader = new VirtualEntityStorageClientLoader($this->storageClientManager);
          $virtualEntity = $clientLoader->getStorageClient($bundle)->load($virtualId);
          if ($virtualEntity) {
            // While loading virtual entities, force them not new entities.
            $entity = $this->create([$this->entityType->getKey('bundle') => $bundle])->mapObject($virtualEntity)->enforceIsNew(FALSE);
            $entities[$id] = $entity;
          }
        }
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    // TODO: Implement has() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.virtual';
  }

  /**
   * {@inheritdoc}
   */
  public function countFieldData($storage_definition, $as_bool = FALSE) {
    // TODO: Implement countFieldData() method.
  }

}
