<?php

namespace Drupal\badge\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Badge entities.
 *
 * @ingroup badge
 */
interface BadgeInterface extends ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Badge name.
   *
   * @return string
   *   Name of the Badge.
   */
  public function getName();

  /**
   * Sets the Badge name.
   *
   * @param string $name
   *   The Badge name.
   *
   * @return \Drupal\badge\Entity\BadgeInterface
   *   The called Badge entity.
   */
  public function setName($name);

  /**
   * Gets the Badge creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Badge.
   */
  public function getCreatedTime();

  /**
   * Sets the Badge creation timestamp.
   *
   * @param int $timestamp
   *   The Badge creation timestamp.
   *
   * @return \Drupal\badge\Entity\BadgeInterface
   *   The called Badge entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Badge published status indicator.
   *
   * Unpublished Badge are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Badge is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Badge.
   *
   * @param bool $published
   *   TRUE to set this Badge to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\badge\Entity\BadgeInterface
   *   The called Badge entity.
   */
  public function setPublished($published);

}
