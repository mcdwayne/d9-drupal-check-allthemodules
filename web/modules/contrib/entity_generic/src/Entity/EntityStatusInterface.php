<?php

namespace Drupal\entity_generic\Entity;

/**
 * Defines an interface for statuses.
 */
interface EntityStatusInterface {

  /**
   * Denotes that the entity is disabled.
   */
  const ENTITY_DISABLED = 0;

  /**
   * Denotes that the entity is enabled.
   */
  const ENTITY_ENABLED = 1;

  /**
   * Returns the entity status.
   *
   * Unpublished entities are only visible to their authors and to administrators.
   *
   * @return bool
   *   TRUE if the entity is active.
   */
  public function isActive();

  /**
   * Gets the entity status.
   *
   * @return bool
   *   TRUE if the entity is active.
   */
  public function getStatus();

  /**
   * Sets the active status of an entity.
   *
   * @param bool $active
   *   TRUE to set this entity to active, FALSE to set it to inactive.
   *
   * @return \Drupal\entity_generic\Entity\SimpleInterface
   *   The called entity.
   */
  public function setStatus($active);

}
