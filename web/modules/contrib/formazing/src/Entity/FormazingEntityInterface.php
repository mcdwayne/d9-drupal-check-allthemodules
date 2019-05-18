<?php

namespace Drupal\formazing\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Formazing entity entities.
 *
 * @ingroup formazing
 */
interface FormazingEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Formazing entity name.
   *
   * @return string
   *   Name of the Formazing entity.
   */
  public function getName();

  /**
   * Sets the Formazing entity name.
   *
   * @param string $name
   *   The Formazing entity name.
   *
   * @return \Drupal\formazing\Entity\FormazingEntityInterface
   *   The called Formazing entity entity.
   */
  public function setName($name);

  /**
   * Gets the Formazing entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Formazing entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Formazing entity creation timestamp.
   *
   * @param int $timestamp
   *   The Formazing entity creation timestamp.
   *
   * @return \Drupal\formazing\Entity\FormazingEntityInterface
   *   The called Formazing entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Formazing entity published status indicator.
   *
   * Unpublished Formazing entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Formazing entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Formazing entity.
   *
   * @param bool $published
   *   TRUE to set this Formazing entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\formazing\Entity\FormazingEntityInterface
   *   The called Formazing entity entity.
   */
  public function setPublished($published);

}
