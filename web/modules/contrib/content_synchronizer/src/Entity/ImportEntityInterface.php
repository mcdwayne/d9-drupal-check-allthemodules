<?php

namespace Drupal\content_synchronizer\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Import entities.
 *
 * @ingroup content_synchronizer
 */
interface ImportEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Import name.
   *
   * @return string
   *   Name of the Import.
   */
  public function getName();

  /**
   * Sets the Import name.
   *
   * @param string $name
   *   The Import name.
   *
   * @return \Drupal\content_synchronizer\Entity\ImportEntityInterface
   *   The called Import entity.
   */
  public function setName($name);

  /**
   * Gets the Import creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Import.
   */
  public function getCreatedTime();

  /**
   * Sets the Import creation timestamp.
   *
   * @param int $timestamp
   *   The Import creation timestamp.
   *
   * @return \Drupal\content_synchronizer\Entity\ImportEntityInterface
   *   The called Import entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Import published status indicator.
   *
   * Unpublished Import are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Import is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Import.
   *
   * @param bool $published
   *   TRUE to set this Import to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\content_synchronizer\Entity\ImportEntityInterface
   *   The called Import entity.
   */
  public function setPublished($published);

}
