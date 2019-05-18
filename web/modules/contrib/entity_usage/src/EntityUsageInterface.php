<?php

namespace Drupal\entity_usage;

use Drupal\Core\Entity\EntityInterface;

/**
 * Entity usage interface.
 */
interface EntityUsageInterface {

  /**
   * Records that an entity is referencing another entity.
   *
   * Examples:
   * - A node that references another node using an entityreference field.
   *
   * @param int $t_id
   *   The identifier of the target entity.
   * @param string $t_type
   *   The type of the target entity.
   * @param int $re_id
   *   The identifier of the referencing entity.
   * @param string $re_type
   *   The type of the entity that is referencing.
   * @param string $method
   *   (optional) The method or way the two entities are being referenced.
   *   Defaults to 'entity_reference'.
   * @param int $count
   *   (optional) The number of references to add to the object. Defaults to 1.
   */
  public function add($t_id, $t_type, $re_id, $re_type, $method = 'entity_reference', $count = 1);

  /**
   * Remove a record indicating that the entity is not being referenced anymore.
   *
   * @param int $t_id
   *   The identifier of the target entity.
   * @param string $t_type
   *   The type of the target entity.
   * @param int $re_id
   *   (optional) The unique, numerid ID of the object containing the referenced
   *   entity. May be omitted if all references to an entity are being deleted.
   *   Defaults to NULL.
   * @param string $re_type
   *   (optional) The type of the object containing the referenced entity. May
   *   be omitted if all entity-type references to a file are being deleted.
   *   Defaults to NULL.
   * @param int $count
   *   (optional) The number of references to delete from the object. Defaults
   *   to 1. Zero may be specified to delete all references to the entity within
   *   a specific object.
   */
  public function delete($t_id, $t_type, $re_id = NULL, $re_type = NULL, $count = 1);

  /**
   * Remove all records of a given entity_type (target).
   *
   * @param string $t_type
   *   The type of the target entity (referenced).
   */
  public function bulkDeleteTargets($t_type);

  /**
   * Remove all records of a given entity_type (host).
   *
   * @param string $re_type
   *   The type of the referencing entity (host).
   */
  public function bulkDeleteHosts($re_type);

  /**
   * Determines where an entity is used.
   *
   * Examples:
   *  - Return example 1:
   *  [
   *    'node' => [
   *      123 => 1,
   *      124 => 1,
   *    ],
   *    'user' => [
   *      2 => 1,
   *    ],
   *  ]
   *  - Return example 2:
   *  [
   *    'entity_reference' => [
   *      'node' => [...],
   *      'user' => [...],
   *    ]
   *  ]
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A target (referenced) entity.
   * @param bool $include_method
   *   (optional) Whether the results must be wrapped into an additional array
   *   level, by the reference method. Defaults to FALSE.
   *
   * @return array
   *   A nested array with usage data. The first level is keyed by the type of
   *   the referencing entities, the second by the referencing objects id. The
   *   value of the second level contains the usage count.
   *   Note that if $include_method is TRUE, the first level is keyed by the
   *   reference method, and the second level will continue as explained above.
   */
  public function listUsage(EntityInterface $entity, $include_method = FALSE);

  /**
   * Determines referenced entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check for references.
   *
   * @return array
   *   A nested array with usage data. The first level is keyed by the type of
   *   the referencing entities, the second by the referencing objects id. The
   *   value of the second level contains the usage count.
   */
  public function listReferencedEntities(EntityInterface $entity);

}
