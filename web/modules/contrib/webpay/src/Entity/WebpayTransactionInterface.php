<?php

namespace Drupal\webpay\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Webpay transaction entities.
 *
 * @ingroup webpay
 */
interface WebpayTransactionInterface extends ContentEntityInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Webpay transaction creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Webpay transaction.
   */
  public function getCreatedTime();

  /**
   * Sets the Webpay transaction creation timestamp.
   *
   * @param int $timestamp
   *   The Webpay transaction creation timestamp.
   *
   * @return \Drupal\webpay\Entity\WebpayTransactionInterface
   *   The called Webpay transaction entity.
   */
  public function setCreatedTime($timestamp);
}
