<?php

namespace Drupal\webform_cart\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Webform cart item entity entities.
 *
 * @ingroup webform_cart
 */
interface WebformCartItemInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Webform cart item entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Webform cart item entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Webform cart item entity creation timestamp.
   *
   * @param int $timestamp
   *   The Webform cart item entity creation timestamp.
   *
   * @return \Drupal\webform_cart\Entity\WebformCartItemInterface
   *   The called Webform cart item entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Webform cart item entity published status indicator.
   *
   * Unpublished Webform cart item entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Webform cart item entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Webform cart item entity.
   *
   * @param bool $published
   *   TRUE to set this Webform cart item entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\webform_cart\Entity\WebformCartItemInterface
   *   The called Webform cart item entity entity.
   */
  public function setPublished($published);

}
