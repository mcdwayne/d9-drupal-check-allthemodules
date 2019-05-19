<?php

/**
 * @file
 * Contains \Drupal\temporal\TemporalInterface.
 */

namespace Drupal\temporal;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Temporal entities.
 *
 * @ingroup temporal
 */
interface TemporalInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Temporal type.
   *
   * @return string
   *   The Temporal type.
   */
  public function getType();

  /**
   * Gets the Temporal name.
   *
   * @return string
   *   Name of the Temporal.
   */
  public function getName();

  /**
   * Sets the Temporal name.
   *
   * @param string $name
   *   The Temporal name.
   *
   * @return \Drupal\temporal\TemporalInterface
   *   The called Temporal entity.
   */
  public function setName($name);

  /**
   * Gets the Temporal creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Temporal.
   */
  public function getCreatedTime();

  /**
   * Sets the Temporal creation timestamp.
   *
   * @param int $timestamp
   *   The Temporal creation timestamp.
   *
   * @return \Drupal\temporal\TemporalInterface
   *   The called Temporal entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Temporal published status indicator.
   *
   * Unpublished Temporal are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Temporal is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Temporal.
   *
   * @param bool $published
   *   TRUE to set this Temporal to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\temporal\TemporalInterface
   *   The called Temporal entity.
   */
  public function setPublished($published);

}
