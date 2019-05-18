<?php

namespace Drupal\badge\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Badge awarded entities.
 *
 * @ingroup badge
 */
interface BadgeAwardedInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Badge awarded creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Badge awarded.
   */
  public function getCreatedTime();

  /**
   * Sets the Badge awarded creation timestamp.
   *
   * @param int $timestamp
   *   The Badge awarded creation timestamp.
   *
   * @return \Drupal\badge\Entity\BadgeAwardedInterface
   *   The called Badge awarded entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Badge awarded published status indicator.
   *
   * Unpublished Badge awarded are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Badge awarded is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Badge awarded.
   *
   * @param bool $published
   *   TRUE to set this Badge awarded to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\badge\Entity\BadgeAwardedInterface
   *   The called Badge awarded entity.
   */
  public function setPublished($published);

}
