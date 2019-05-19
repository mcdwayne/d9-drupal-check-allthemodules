<?php

namespace Drupal\uuid_map;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Implements the UuidMapper class.
 */
class UuidMapper implements UuidMapperInterface {

  /**
   * Advanced Access Table name.
   *
   * @var string
   */
  const TABLE_NAME = "uuid_map";

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UuidMapper object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type manager to get entity storage.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function insertEntity(EntityInterface $entity) {
    $uuid = $entity->uuid();
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $query = $this->database
      ->insert(static::TABLE_NAME)
      ->fields([
        'uuid',
        'entity_type',
        'entity_id',
      ])
      ->values([
        $uuid,
        $entity_type,
        $entity_id,
      ]);
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntity(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();
    $query = $this->database
      ->delete(static::TABLE_NAME)
      ->condition('entity_type', $entity_type)
      ->condition('entity_id', $entity_id);
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function lookupUuid($uuid) {
    $query = $this->database
      ->select(static::TABLE_NAME, 'base')
      ->fields('base', ['uuid', 'entiy_type', 'entity_id'])
      ->condition('base.uuid', $uuid)
      ->rante(0, 1);
    $result = $query->execute()->fetchAssoc();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupUuidMultiple(array $uuids) {
    $query = $this->database
      ->select(static::TABLE_NAME, 'base')
      ->fields('base', ['uuid', 'entiy_type', 'entity_id'])
      ->condition('base.uuid', $uuids, 'IN')
      ->rante(0, 1);
    $results = $query->execute()->fetchAllAssoc();
    $types = [];
    foreach ($results as $result) {
      if (!isset($types[$entity['entity_type']])) {
        $types[$entity['entity_type']] = [];
      }
      $types[$entity['entity_type']][] = $result;
    }
    return $types;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityByUuid($uuid) {
    $result = $this->lookupUuid($uuid);
    if ($result) {
      $storage = $this->entityTypeManager->getStorage($result['entity_type']);
      if ($storage) {
        return $storage->load($result['entity_id']);
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityByUuidMultiple(array $uuids, $grouped = TRUE) {
    $results = $this->lookupUuid($uuid);
    $final_map = [];
    foreach ($results as $entity_type => $entries) {
      $mapped_entities = [];
      $type_storage = $this->entityTypeManager->getStorage($entity_type);
      if ($type_storage) {
        // Load all entites of the given type.
        $entity_ids = array_column($entries, 'entity_id');
        $entities = $type_storage->load($entity_ids);
        foreach ($entities as $entity) {
          // Return set should map the uuid to an entity.
          $mapped_entities[$entity->uuid()] = $entity;
        }
        // Only return results for this type, if there are entities.
        if (count($mapped_entities)) {
          // If grouping entities by type add under key.
          if ($grouped) {
            $final_map[$entity_type] = $mapped_entities;
          }
          // If results are not grouped, merge the arrays.
          else {
            $final_map += $mapped_entities;
          }
        }
      }
    }
    return $final_map;
  }

}
