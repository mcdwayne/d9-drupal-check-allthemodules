<?php

namespace Drupal\path_file\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Path file entity entities.
 *
 * @ingroup path_file
 */
interface PathFileEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Path file entity name.
   *
   * @return string
   *   Name of the Path file entity.
   */
  public function getName();

  /**
   * Sets the Path file entity name.
   *
   * @param string $name
   *   The Path file entity name.
   *
   * @return \Drupal\path_file\Entity\PathFileEntityInterface
   *   The called Path file entity entity.
   */
  public function setName($name);

  /**
   * Gets the Path file entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Path file entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Path file entity creation timestamp.
   *
   * @param int $timestamp
   *   The Path file entity creation timestamp.
   *
   * @return \Drupal\path_file\Entity\PathFileEntityInterface
   *   The called Path file entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Path file entity published status indicator.
   *
   * Unpublished Path file entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Path file entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Path file entity.
   *
   * @param bool $published
   *   TRUE to set this Path file entity to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\path_file\Entity\PathFileEntityInterface
   *   The called Path file entity entity.
   */
  public function setPublished($published);

  /**
   * Gets the File ID.
   *
   * @return int
   *   The target_id of the file associated with this entity.
   */
  public function getFid();

}
