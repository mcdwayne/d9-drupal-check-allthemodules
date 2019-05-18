<?php

namespace Drupal\hidden_tab\Entity\Base;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Denotes entities having created, changed and status fields.
 */
interface TimestampedEntityInterface extends EntityChangedInterface, EntityInterface {

  /**
   * Set the entity creation time.
   *
   * @param int $timestamp
   *   The entity creation timestamp.
   */
  public function setCreatedTime($timestamp);

  /**
   * The timestamp the entity was created at.
   *
   * @return int
   *   The timestamp the entity was created at.
   */
  public function getCreatedTime();

}
