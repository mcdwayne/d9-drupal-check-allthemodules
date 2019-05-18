<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseBasicInterface.
 */

namespace Drupal\entity_base\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines a common interface for entities.
 *
 * @ingroup entity_api
 */
interface EntityBaseBasicInterface extends ContentEntityInterface, EntityChangedInterface {

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
   * @return \Drupal\entity_base\Entity\EntityBaseBasicInterface
   *   The called entity.
   */
  public function setCreatedTime($timestamp);

}
