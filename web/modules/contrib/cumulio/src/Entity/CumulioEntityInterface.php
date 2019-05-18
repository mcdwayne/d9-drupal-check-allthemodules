<?php

namespace Drupal\cumulio\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cumulio entity entities.
 *
 * @ingroup cumulio
 */
interface CumulioEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Cumulio entity name.
   *
   * @return string
   *   Name of the Cumulio entity.
   */
  public function getName();

  /**
   * Sets the Cumulio entity name.
   *
   * @param string $name
   *   The Cumulio entity name.
   *
   * @return \Drupal\cumulio\Entity\CumulioEntityInterface
   *   The called Cumulio entity entity.
   */
  public function setName($name);

  /**
   * Gets the Cumulio entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Cumulio entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Cumulio entity creation timestamp.
   *
   * @param int $timestamp
   *   The Cumulio entity creation timestamp.
   *
   * @return \Drupal\cumulio\Entity\CumulioEntityInterface
   *   The called Cumulio entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Cumulio entity published status indicator.
   *
   * Unpublished Cumulio entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Cumulio entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Cumulio entity.
   *
   * @param bool $published
   *   TRUE to set this Cumulio entity to published, FALSE to set it to
   *   unpublished.
   *
   * @return \Drupal\cumulio\Entity\CumulioEntityInterface
   *   The called Cumulio entity entity.
   */
  public function setPublished($published);

}
