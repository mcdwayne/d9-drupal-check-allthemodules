<?php

namespace Drupal\entity_generic\Entity;

/**
 * Defines an interface for archived and archived_time field.
 */
interface EntityArchivedInterface {

  /**
   * Denotes that the entity is archived.
   */
  const ENTITY_GENERIC_ARCHIVED = 1;

  /**
   * Denotes that the entity is unarchived.
   */
  const ENTITY_GENERIC_UNARCHIVED = 0;

  /**
   * Returns the entity archived status.
   *
   * @return bool
   *   TRUE if the entity is archived.
   */
  public function isArchived();

  /**
   * Gets the entity archived status.
   *
   * @return bool
   *   TRUE if the entity is archived.
   */
  public function getArchived();

  /**
   * Sets the archived status of an entity.
   *
   * @param bool $archived
   *   TRUE to set this entity to archived, FALSE to set it to unarchived.
   *
   * @return \Drupal\entity_generic\Entity\GenericInterface
   *   The called entity.
   */
  public function setArchived($archived);

  /**
   * Gets the entity archived timestamp.
   *
   * @return int
   *   Archived timestamp of the entity.
   */
  public function getArchivedTime();

  /**
   * Sets the entity archived timestamp.
   *
   * @param int $timestamp
   *   The entity archived timestamp.
   *
   * @return \Drupal\entity_generic\Entity\GenericInterface
   *   The called entity.
   */
  public function setArchivedTime($timestamp);

}
