<?php

namespace Drupal\content_synchronizer\Service;

use Drupal\Core\Entity\EntityInterface;

/**
 * The global reference  manager.
 */
class GlobalReferenceManager {

  const SERVICE_NAME = 'content_synchronizer.global_reference_manager';

  // GID DATA.
  const GID_TABLE_NAME = 'content_synchronizer_global_reference';
  const FIELD_GID = 'gid';
  const FIELD_ENTITY_ID = 'entity_id';
  const FIELD_ENTITY_TYPE = 'entity_type';

  // BUFFER DATA.
  const BUFFER_TABLE_NAME = 'content_synchronizer_reference_buffer';
  const FIELD_PARENT_ENTITY_ID = 'entity_gid';
  const FIELD_REFERENCED_ENTITY_ID = 'referenced_entity_gid';
  const FIELD_FIELD_NAME = 'field_name';
  const FIELD_ORDER = 'item_order';

  /**
   * Get the global id of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The gid.
   */
  public function getEntityGlobalId(EntityInterface $entity) {

    $query = \Drupal::database()->select(self::GID_TABLE_NAME)
      ->fields(self::GID_TABLE_NAME, [self::FIELD_GID])
      ->condition(self::FIELD_ENTITY_TYPE, $entity->getEntityTypeId())
      ->condition(self::FIELD_ENTITY_ID, $entity->id());

    if ($result = $query->execute()->fetchField()) {
      return $result;
    }
    return NULL;
  }

  /**
   * Create GID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The gid.
   */
  public function createEntityGlobalId(EntityInterface $entity) {
    $gid = ((int) (microtime(TRUE) * 100)) . '.' . $entity->getEntityTypeId() . '.' . $entity->id();

    $data = [
      self::FIELD_GID         => $gid,
      self::FIELD_ENTITY_ID   => $entity->id(),
      self::FIELD_ENTITY_TYPE => $entity->getEntityTypeId(),
    ];

    \Drupal::database()->insert(self::GID_TABLE_NAME)
      ->fields(array_keys($data))
      ->values($data)
      ->execute();
    return $gid;
  }

  /**
   * Get the entity by is gid.
   *
   * @param string $gid
   *   The gid.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  public function getEntityByGid($gid) {

    $query = \Drupal::database()->select(self::GID_TABLE_NAME)
      ->fields(self::GID_TABLE_NAME, [
        self::FIELD_ENTITY_TYPE,
        self::FIELD_ENTITY_ID,
      ])
      ->condition(self::FIELD_GID, $gid);

    if ($result = $query->execute()->fetchAssoc()) {
      return \Drupal::entityTypeManager()
        ->getStorage($result[self::FIELD_ENTITY_TYPE])
        ->load($result[self::FIELD_ENTITY_ID]);
    }
    return NULL;
  }

  /**
   * Return the entity type from the gid.
   *
   * @param string $gid
   *   THe gid.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeFromGid($gid) {
    list($time, $entityTypeId, $entityLocalId) = explode('.', $gid);

    return $entityTypeId;
  }

  /**
   * Create GID from entity and gid.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $gid
   *   THe gid.
   */
  public function createGlobalEntityByImportingEntityAndGid(EntityInterface $entity, $gid) {
    $data = [
      self::FIELD_GID         => $gid,
      self::FIELD_ENTITY_ID   => $entity->id(),
      self::FIELD_ENTITY_TYPE => $entity->getEntityTypeId(),
    ];

    try {
      \Drupal::database()->insert(self::GID_TABLE_NAME)
        ->fields(array_keys($data))
        ->values($data)
        ->execute();
    }
    catch (\Exception $e) {
      // Mute exception...
    }
  }

  /**
   * Delete the gid on entity deletion.
   */
  public function onEntityDelete(EntityInterface $entity) {
    \Drupal::database()->delete(self::GID_TABLE_NAME)
      ->condition(self::FIELD_ENTITY_ID, $entity->id())
      ->condition(self::FIELD_ENTITY_TYPE, $entity->getEntityTypeId())
      ->execute();
  }

  /**
   * Return the entity by gid and uuid.
   */
  public function getExistingEntityByGidAndUuid($gid, $uuid) {
    // Load by gid for already imported or exported data :
    if ($existing = $this->getEntityByGid($gid)) {
      return $existing;
    }

    // Load by uuid :
    try {
      $entityType = $this->getEntityTypeFromGid($gid);
      $query = \Drupal::entityQuery($entityType)->condition('uuid', $uuid);

      $result = $query->execute();
      if (!empty($result)) {

        // Get the entity.
        $entity = \Drupal::entityTypeManager()
          ->getStorage($entityType)
          ->load(reset($result));

        // Create the global reference association.
        $this->createGlobalEntityByImportingEntityAndGid($entity, $gid);

        return $entity;
      }
    }
    catch (\Exception $e) {
      // Mute exception.
    }

    return NULL;
  }

}
