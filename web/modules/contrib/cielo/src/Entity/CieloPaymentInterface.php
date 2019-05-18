<?php

namespace Drupal\cielo\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cielo credit card payment entities.
 *
 * @ingroup cielo
 */
interface CieloPaymentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Cielo credit card payment name.
   *
   * @return string
   *   Name of the Cielo credit card payment.
   */
  public function getName();

  /**
   * Sets the Cielo credit card payment name.
   *
   * @param string $name
   *   The Cielo credit card payment name.
   *
   * @return \Drupal\cielo\Entity\CieloPaymentInterface
   *   The called Cielo credit card payment entity.
   */
  public function setName($name);

  /**
   * Gets the Cielo credit card payment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Cielo credit card payment.
   */
  public function getCreatedTime();

  /**
   * Sets the Cielo credit card payment creation timestamp.
   *
   * @param int $timestamp
   *   The Cielo credit card payment creation timestamp.
   *
   * @return \Drupal\cielo\Entity\CieloPaymentInterface
   *   The called Cielo credit card payment entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Cielo credit card payment published status indicator.
   *
   * Unpublished Cielo credit card payment are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Cielo credit card payment is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Cielo credit card payment.
   *
   * @param bool $published
   *   TRUE to set this Cielo credit card payment to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\cielo\Entity\CieloPaymentInterface
   *   The called Cielo credit card payment entity.
   */
  public function setPublished($published);

}
