<?php

namespace Drupal\splashify\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Splashify entity entities.
 *
 * @ingroup splashify
 */
interface SplashifyEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Splashify entity name.
   *
   * @return string
   *   Name of the Splashify entity.
   */
  public function getName();

  /**
   * Sets the Splashify entity name.
   *
   * @param string $name
   *   The Splashify entity name.
   *
   * @return \Drupal\splashify\Entity\SplashifyEntityInterface
   *   The called Splashify entity entity.
   */
  public function setName($name);

  /**
   * Gets the Splashify entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Splashify entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Splashify entity creation timestamp.
   *
   * @param int $timestamp
   *   The Splashify entity creation timestamp.
   *
   * @return \Drupal\splashify\Entity\SplashifyEntityInterface
   *   The called Splashify entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Splashify entity published status indicator.
   *
   * Unpublished Splashify entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Splashify entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Splashify entity.
   *
   * @param bool $published
   *   TRUE to set this Splashify entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\splashify\Entity\SplashifyEntityInterface
   *   The called Splashify entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the Splashify entity content.
   *
   * @return string
   */
  public function getContent();

  /**
   * Gets the Splashify entity group target_id.
   *
   * @return string
   */
  public function getGroupId();

}
