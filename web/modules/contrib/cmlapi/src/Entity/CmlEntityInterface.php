<?php

namespace Drupal\cmlapi\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Cml entity entities.
 *
 * @ingroup cmlapi
 */
interface CmlEntityInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Cml entity name.
   *
   * @return string
   *   Name of the Cml entity.
   */
  public function getName();

  /**
   * Sets the Cml entity name.
   *
   * @param string $name
   *   The Cml entity name.
   *
   * @return \Drupal\cmlapi\Entity\CmlEntityInterface
   *   The called Cml entity entity.
   */
  public function setName($name);

  /**
   * Gets the Cml entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Cml entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Cml entity creation timestamp.
   *
   * @param int $timestamp
   *   The Cml entity creation timestamp.
   *
   * @return \Drupal\cmlapi\Entity\CmlEntityInterface
   *   The called Cml entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Cml entity published status indicator.
   *
   * Unpublished Cml entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Cml entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Cml entity.
   *
   * @param bool $published
   *   TRUE to set this Cml entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\cmlapi\Entity\CmlEntityInterface
   *   The called Cml entity entity.
   */
  public function setPublished($published);

}
