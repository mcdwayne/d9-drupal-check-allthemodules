<?php

namespace Drupal\copyscape\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Copyscape fail entities.
 *
 * @ingroup copyscape
 */
interface CopyscapeFailInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Copyscape fail creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Copyscape fail.
   */
  public function getCreatedTime();

  /**
   * Sets the Copyscape fail creation timestamp.
   *
   * @param int $timestamp
   *   The Copyscape fail creation timestamp.
   *
   * @return \Drupal\copyscape\Entity\CopyscapeFailInterface
   *   The called Copyscape fail entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Copyscape fails count.
   *
   * @return int
   *   Count of Copyscape fails.
   */
  public function getFails();

  /**
   * Sets the Copyscape fails count.
   *
   * @param int $count
   *   The Copyscape fail count.
   *
   * @return \Drupal\copyscape\Entity\CopyscapeFailInterface
   *   The called Copyscape fail entity.
   */
  public function setFails($count);

}
