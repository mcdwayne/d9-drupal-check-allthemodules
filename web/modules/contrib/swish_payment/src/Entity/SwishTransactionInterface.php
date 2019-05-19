<?php

namespace Drupal\swish_payment\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Swish transaction entities.
 *
 * @ingroup swish_payment
 */
interface SwishTransactionInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Swish transaction name.
   *
   * @return string
   *   Name of the Swish transaction.
   */
  public function getTransactionId();

  /**
   * Sets the Swish transaction name.
   *
   * @param string $name
   *   The Swish transaction name.
   *
   * @return \Drupal\swish_payment\Entity\SwishTransactionInterface
   *   The called Swish transaction entity.
   */
  public function setTransactionId($name);

  /**
   * Gets the Swish transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Swish transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the Swish transaction creation timestamp.
   *
   * @param int $timestamp
   *   The Swish transaction creation timestamp.
   *
   * @return \Drupal\swish_payment\Entity\SwishTransactionInterface
   *   The called Swish transaction entity.
   */
  public function setCreatedTime($timestamp);

}
