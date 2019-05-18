<?php

namespace Drupal\formazing\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Result formazing entity entities.
 *
 * @ingroup formazing
 */
interface ResultFormazingEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Result formazing entity name.
   *
   * @return string
   *   Name of the Result formazing entity.
   */
  public function getName();

  /**
   * Sets the Result formazing entity name.
   *
   * @param string $name
   *   The Result formazing entity name.
   *
   * @return \Drupal\formazing\Entity\ResultFormazingEntityInterface
   *   The called Result formazing entity entity.
   */
  public function setName($name);

  /**
   * Gets the Result formazing entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Result formazing entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Result formazing entity creation timestamp.
   *
   * @param int $timestamp
   *   The Result formazing entity creation timestamp.
   *
   * @return \Drupal\formazing\Entity\ResultFormazingEntityInterface
   *   The called Result formazing entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Result formazing entity published status indicator.
   *
   * Unpublished Result formazing entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Result formazing entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Result formazing entity.
   *
   * @param bool $published
   *   TRUE to set this Result formazing entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\formazing\Entity\ResultFormazingEntityInterface
   *   The called Result formazing entity entity.
   */
  public function setPublished($published);

}
