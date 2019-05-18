<?php

namespace Drupal\content_synchronizer\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Export entity entities.
 *
 * @ingroup content_synchronizer
 */
interface ExportEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Export entity name.
   *
   * @return string
   *   Name of the Export entity.
   */
  public function getName();

  /**
   * Sets the Export entity name.
   *
   * @param string $name
   *   The Export entity name.
   *
   * @return \Drupal\content_synchronizer\Entity\ExportEntityInterface
   *   The called Export entity entity.
   */
  public function setName($name);

  /**
   * Gets the Export entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Export entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Export entity creation timestamp.
   *
   * @param int $timestamp
   *   The Export entity creation timestamp.
   *
   * @return \Drupal\content_synchronizer\Entity\ExportEntityInterface
   *   The called Export entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Export entity published status indicator.
   *
   * Unpublished Export entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Export entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Export entity.
   *
   * @param bool $published
   *   TRUE to set this Export entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\content_synchronizer\Entity\ExportEntityInterface
   *   The called Export entity entity.
   */
  public function setPublished($published);

}
