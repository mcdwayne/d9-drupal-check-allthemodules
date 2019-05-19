<?php

namespace Drupal\splashify\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Splashify group entity entities.
 *
 * @ingroup splashify
 */
interface SplashifyGroupEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Splashify group entity name.
   *
   * @return string
   *   Name of the Splashify group entity.
   */
  public function getName();

  /**
   * Sets the Splashify group entity name.
   *
   * @param string $name
   *   The Splashify group entity name.
   *
   * @return \Drupal\splashify\Entity\SplashifyGroupEntityInterface
   *   The called Splashify group entity entity.
   */
  public function setName($name);

  /**
   * Gets the Splashify group entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Splashify group entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Splashify group entity creation timestamp.
   *
   * @param int $timestamp
   *   The Splashify group entity creation timestamp.
   *
   * @return \Drupal\splashify\Entity\SplashifyGroupEntityInterface
   *   The called Splashify group entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Splashify group entity published status indicator.
   *
   * Unpublished Splashify group entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Splashify group entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Splashify group entity.
   *
   * @param bool $published
   *   TRUE to set this Splashify group entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\splashify\Entity\SplashifyGroupEntityInterface
   *   The called Splashify group entity entity.
   */
  public function setPublished($published);

}
