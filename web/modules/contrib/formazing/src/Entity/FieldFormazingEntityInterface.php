<?php

namespace Drupal\formazing\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Field formazing entity entities.
 *
 * @ingroup formazing
 */
interface FieldFormazingEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Field formazing entity name.
   *
   * @return string
   *   Name of the Field formazing entity.
   */
  public function getName();

  /**
   * Sets the Field formazing entity name.
   *
   * @param string $name
   *   The Field formazing entity name.
   *
   * @return \Drupal\formazing\Entity\FieldFormazingEntityInterface
   *   The called Field formazing entity entity.
   */
  public function setName($name);

  /**
   * Gets the Field formazing entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Field formazing entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Field formazing entity creation timestamp.
   *
   * @param int $timestamp
   *   The Field formazing entity creation timestamp.
   *
   * @return \Drupal\formazing\Entity\FieldFormazingEntityInterface
   *   The called Field formazing entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Field formazing entity published status indicator.
   *
   * Unpublished Field formazing entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Field formazing entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Field formazing entity.
   *
   * @param bool $published
   *   TRUE to set this Field formazing entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\formazing\Entity\FieldFormazingEntityInterface
   *   The called Field formazing entity entity.
   */
  public function setPublished($published);

}
