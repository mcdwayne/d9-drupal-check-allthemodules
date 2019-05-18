<?php

namespace Drupal\copyscape\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Copyscape result entities.
 *
 * @ingroup copyscape
 */
interface CopyscapeResultInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Copyscape result creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Copyscape result.
   */
  public function getCreatedTime();

  /**
   * Sets the Copyscape result creation timestamp.
   *
   * @param int $timestamp
   *   The Copyscape result creation timestamp.
   *
   * @return \Drupal\copyscape\Entity\CopyscapeResultInterface
   *   The called Copyscape result entity.
   */
  public function setCreatedTime($timestamp);
}
