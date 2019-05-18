<?php

namespace Drupal\entity_generic\Entity;

/**
 * Defines an interface for created field.
 */
interface EntityCreatedInterface {

  /**
   * Gets the entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity.
   */
  public function getCreatedTime();

  /**
   * Sets the entity creation timestamp.
   *
   * @param int $timestamp
   *   The entity creation timestamp.
   *
   * @return \Drupal\entity_generic\Entity\BasicInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

}
