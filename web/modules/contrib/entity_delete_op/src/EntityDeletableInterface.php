<?php

namespace Drupal\entity_delete_op;

/**
 * Provides an interface for defining deletable entities.
 */
interface EntityDeletableInterface {

  /**
   * Returns this entity's entity type object.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface
   *   The entity type object.
   */
  public function getEntityType();

  /**
   * Checks if the entity is marked as deleted.
   *
   * @return bool
   *   Returns TRUE if marked as deleted, otherwise FALSE.
   */
  public function isDeleted();

  /**
   * Marks the entity as deleted or not.
   *
   * @param bool $value
   *   A boolean indicating whether entity should be marked as deleted.
   *
   * @return \Drupal\entity_delete_op\EntityDeletableInterface
   *   Self.
   */
  public function setIsDeleted($value);

  /**
   * Performs necessary actions to persist deleted state flag.
   *
   * @see \Drupal\Core\Entity\EntityInterface::save()
   */
  public function save();

  /**
   * Permanently removes the entity from persistent storage.
   *
   * @see \Drupal\Core\Entity\EntityInterface::delete()
   */
  public function delete();

}
