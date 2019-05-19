<?php

namespace Drupal\uuid_map;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines api for UuidMapper Service.
 */
interface UuidMapperInterface {

  /**
   * When an entity is inserted into the database, add a mapping entry.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Then entity being inserted.
   */
  public function insertEntity(EntityInterface $entity);

  /**
   * When an entity is deleted from the database, drop the mapping entry.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Then entity being deleted.
   */
  public function deleteEntity(EntityInterface $entity);

  /**
   * Lookup the entity type an id for an entity with a given uuid.
   *
   * Returned array will have the following structure.
   *
   * [
   *   'entity_type' => 'node',
   *   'entity_id' => '1',
   * ]
   *
   * @param string $uuid
   *   The uuid to lookup.
   *
   * @return array
   *   If the mapping exists, an array for the result, otherwise NULL.
   */
  public function lookupUuid($uuid);

  /**
   * Lookup the entity types and ids for multiple uuids.
   *
   * The returned array will follow a similar format to match with lookupUuid.
   *
   * [
   *   'node' => [
   *     'entity_type' => 'node',
   *     'entity_id' => '1',
   *   ],
   * ]
   *
   * @param array $uuids
   *   The uuids to lookup.
   *
   * @return array
   *   An array where keys are entity_types, and value is an array containing
   *   entity type and id of result.
   */
  public function lookupUuidMultiple(array $uuids);

  /**
   * Lookup the entity with a given uuid.
   *
   * @param string $uuid
   *   The uuid to lookup.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Instance of the entity with the given uuid.
   */
  public function getEntityByUuid($uuid);

  /**
   * Lookup the entity with a given uuid.
   *
   * @param array $uuids
   *   The uuids to lookup.
   * @param bool $grouped
   *   Group results by entity type. Defaults to TRUE.
   *
   * @return array
   *   If grouped, returns an array where keys are entity types and value is an
   *   array of Drupal\Core\Entity\EntityInterface. For non grouped results,
   *   array keys, are the supplied uuids and values are the Entity instances.
   */
  public function getEntityByUuidMultiple(array $uuids, $grouped = TRUE);

}
