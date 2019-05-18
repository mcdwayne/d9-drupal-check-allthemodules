<?php

namespace Drupal\cg_payment;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a transaction entity type.
 */
interface TransactionInterface extends ContentEntityInterface {

  /**
   * Gets the transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the transaction creation timestamp.
   *
   * @param int $timestamp
   *   The transaction creation timestamp.
   *
   * @return \Drupal\registration_payment\TransactionInterface
   *   The called transaction entity.
   */
  public function setCreatedTime($timestamp);

}
