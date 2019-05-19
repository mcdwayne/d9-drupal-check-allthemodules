<?php

namespace Drupal\webfactory_slave\Services;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\webfactory\EntityImportEvent;
use Drupal\webfactory_slave\MasterClientInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides Entity synchronization from master site.
 *
 * @package Drupal\webfactory_slave\Services
 */
class EntitySynchronizer {

  /**
   * Master client instance.
   *
   * @var \Drupal\webfactory_slave\MasterClientInterface
   */
  protected $masterClient;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Channels available for current satellite.
   *
   * @var array
   */
  protected $channels;

  /**
   * Create a new instance of EntitySynchronizer.
   *
   * @param \Drupal\webfactory_slave\MasterClientInterface $client
   *   The master client to use.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   Entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend service.
   */
  public function __construct(MasterClientInterface $client, EventDispatcherInterface $event_dispatcher, EntityFieldManagerInterface $entity_field_manager, EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    $this->masterClient = $client;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cache_backend;

    // Init available channels.
    $this->initChannels();
  }

  /**
   * Return channels retrieved from master.
   *
   * @return array
   *   Satellite channels.
   */
  public function getChannels() {
    return $this->channels;
  }

  /**
   * Retrieve entities from given channel id.
   *
   * @param string $channel_id
   *   The channel id.
   * @param int $limit
   *   Limit number of the entities to retrieve.
   * @param int $offset
   *   Offset of the entities to get.
   *
   * @return array
   *   Remote entities.
   */
  public function getData($channel_id, $limit = NULL, $offset = NULL) {
    if ($channel_id == NULL && !empty($this->channels)) {
      $channel = current($this->channels);
      $channel_id = $channel->id;
    }

    return $this->masterClient->getEntitiesData($channel_id, NULL, $limit, $offset);
  }

  /**
   * Create or update local entity according to given uuid.
   *
   * @param string $channel_id
   *   Channel Id used.
   * @param string $uuid
   *   Uuid of entity.
   */
  public function save($channel_id, $uuid) {
    if ($channel_id == NULL && !empty($this->channels)) {
      $channel = current($this->channels);
      $channel_id = $channel->id;
    }

    $response = $this->masterClient->getEntitiesData($channel_id, $uuid);

    $entity_type = $response['entity_type'];
    $entity_key_id = $this->getEntityKey($entity_type, 'id');
    $entity_key_revision = $this->getEntityKey($entity_type, 'revision');

    foreach ($response['entities'] as $bundle => $entities) {
      foreach ($entities as $entity) {
        $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
        $bundle_prop = $this->getBundleTypeProperty($entity_type);
        $local_entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);

        // Just synchronised existing entity.
        if ($local_entity) {
          foreach ($field_definitions as $field_name => $field) {
            if (isset($entity[$field_name]) && !in_array($field_name, [$entity_key_id, $entity_key_revision])) {
              $local_entity->set($field_name, $entity[$field_name]);
            }
          }
        }
        else {
          // Create a copy.
          $entity['uuid'] = $uuid;
          $entity[$bundle_prop] = $bundle;

          // Remove the hardcoded ids from remote master.
          unset($entity[$entity_key_id], $entity[$entity_key_revision]);

          $local_entity = $this->entityTypeManager
            ->getStorage($entity_type)
            ->create($entity);
        }

        // Dispatch event for altering entities import.
        $this->eventDispatcher->dispatch(EntityImportEvent::EVENT_NAME, new EntityImportEvent($local_entity, $entity, $field_definitions));

        // Create or update the entity in local satellite.
        $local_entity->save();
      }
    }
  }

  /**
   * Helper that extract bundle property from given entity type.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return string|void
   *   Bundle property.
   */
  protected function getBundleTypeProperty($entity_type) {
    return $this->getEntityKey($entity_type, 'bundle');
  }

  /**
   * Helper that extract a property from given entity type.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $key
   *   Entity key to get.
   *
   * @return string|void
   *   Key property.
   */
  protected function getEntityKey($entity_type, $key) {
    $entity_key = NULL;
    $definitions = $this->entityTypeManager->getDefinitions();
    if (isset($definitions[$entity_type])) {
      $entity_keys = $definitions[$entity_type]->get('entity_keys');
      if (isset($entity_keys[$key])) {
        $entity_key = $entity_keys[$key];
      }
    }
    return $entity_key;
  }

  /**
   * Retrieves channels from master and save it into cache (if not empty).
   */
  protected function initChannels() {
    $cid = 'webfactory_slave:channels';

    if ($cache = $this->cacheBackend->get($cid)) {
      $channels = $cache->data;
    }
    else {
      $conf_webfactory_slave = $this->configFactory->getEditable('webfactory_slave.settings');
      $sat_id = $conf_webfactory_slave->get('id');
      $channels = $this->masterClient->getChannelsData($sat_id);

      if (!empty($channels)) {
        $this->cacheBackend->set($cid, $channels);
      }
    }

    $this->channels = $channels;
  }

}
