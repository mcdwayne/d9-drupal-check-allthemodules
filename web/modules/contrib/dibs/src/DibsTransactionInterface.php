<?php

namespace Drupal\dibs;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Dibs transaction entities.
 *
 * @ingroup dibs
 */
interface DibsTransactionInterface extends ContentEntityInterface, EntityChangedInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Dibs transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Dibs transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the Dibs transaction creation timestamp.
   *
   * @param int $timestamp
   *   The Dibs transaction creation timestamp.
   *
   * @return \Drupal\dibs\DibsTransactionInterface
   *   The called Dibs transaction entity.
   */
  public function setCreatedTime($timestamp);

}
