<?php

namespace Drupal\library;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Library transaction entities.
 *
 * @ingroup library
 */
interface LibraryTransactionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Library transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Library transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the Library transaction creation timestamp.
   *
   * @param int $timestamp
   *   The Library transaction creation timestamp.
   *
   * @return \Drupal\library\LibraryTransactionInterface
   *   The called Library transaction entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Get Node ID.
   */
  public function getNid();

  /**
   * Get Patron ID.
   */
  public function getPatron();

}
