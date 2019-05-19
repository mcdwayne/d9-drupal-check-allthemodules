<?php

namespace Drupal\webform_cart\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Webform cart order entities.
 *
 * @ingroup webform_cart
 */
interface WebformCartOrderInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Webform cart order creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Webform cart order.
   */
  public function getCreatedTime();

  /**
   * Sets the Webform cart order creation timestamp.
   *
   * @param int $timestamp
   *   The Webform cart order creation timestamp.
   *
   * @return \Drupal\webform_cart\Entity\WebformCartOrderInterface
   *   The called Webform cart order.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Webform cart order published status indicator.
   *
   * Unpublished Webform cart order are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Webform cart order is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Webform cart order.
   *
   * @param bool $published
   *   TRUE to set this Webform cart order to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\webform_cart\Entity\WebformCartOrderInterface
   *   The called Webform cart order.
   */
  public function setPublished($published);

}
