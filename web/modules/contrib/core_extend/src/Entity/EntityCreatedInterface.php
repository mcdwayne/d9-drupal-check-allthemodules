<?php

namespace Drupal\core_extend\Entity;

/**
 * Provides an interface for defining Organization Role entities.
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
   * @return \Drupal\core_extend\Entity\EntityCreatedInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

}
