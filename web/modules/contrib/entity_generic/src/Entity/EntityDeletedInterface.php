<?php

namespace Drupal\entity_generic\Entity;

/**
 * Defines an interface for "flag qs deleted" and flag_deleted_time fields.
 */
interface EntityDeletedInterface {

  /**
   * Denotes that the entity is glagged as deleted.
   */
  const ENTITY_GENERIC_DELETED = 1;

  /**
   * Denotes that the entity is unflagged as deleted.
   */
  const ENTITY_GENERIC_UNDELETED = 0;

  /**
   * Returns the entity flag as deleted.
   *
   * @return bool
   *   TRUE if the entity is flagged as deleted.
   */
  public function isDeleted();

  /**
   * Gets the entity flag as deleted.
   *
   * @return bool
   *   TRUE if the entity is flagged as deleted.
   */
  public function getDeleted();

  /**
   * Sets the deleted flag of an entity.
   *
   * @param bool $flag_deleted
   *   TRUE to set this entity to be flagged as deleted, FALSE to set it to unflagged as deleted.
   *
   * @return \Drupal\entity_generic\Entity\GenericInterface
   *   The called entity.
   */
  public function setDeleted($flag_deleted);

  /**
   * Gets the entity flag as deleted timestamp.
   *
   * @return int
   *   Deleted timestamp of the entity.
   */
  public function getDeletedTime();

  /**
   * Sets the entity deleted timestamp.
   *
   * @param int $timestamp
   *   The entity deleted timestamp.
   *
   * @return \Drupal\entity_generic\Entity\GenericInterface
   *   The called entity.
   */
  public function setDeletedTime($timestamp);

}
