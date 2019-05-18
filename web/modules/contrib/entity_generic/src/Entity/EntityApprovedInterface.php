<?php

namespace Drupal\entity_generic\Entity;

/**
 * Defines an interface for approved and approved_time fields.
 */
interface EntityApprovedInterface {

  /**
   * Denotes that the entity is approved.
   */
  const ENTITY_GENERIC_APPROVED = 1;

  /**
   * Denotes that the entity is unapproved.
   */
  const ENTITY_GENERIC_UNAPPROVED = 0;

  /**
   * Returns the entity approved status.
   *
   * @return bool
   *   TRUE if the entity is approved.
   */
  public function isApproved();

  /**
   * Gets the entity approved status.
   *
   * @return bool
   *   TRUE if the entity is approved.
   */
  public function getApproved();

  /**
   * Sets the approved status of an entity.
   *
   * @param bool $approved
   *   TRUE to set this entity to approved, FALSE to set it to unapproved.
   *
   * @return \Drupal\entity_generic\Entity\GenericInterface
   *   The called entity.
   */
  public function setApproved($approved);

  /**
   * Gets the entity approved timestamp.
   *
   * @return int
   *   Approved timestamp of the entity.
   */
  public function getApprovedTime();

  /**
   * Sets the entity approved timestamp.
   *
   * @param int $timestamp
   *   The entity approved timestamp.
   *
   * @return \Drupal\entity_generic\Entity\GenericInterface
   *   The called entity.
   */
  public function setApprovedTime($timestamp);

}
