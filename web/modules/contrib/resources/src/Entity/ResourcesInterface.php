<?php

namespace Drupal\resources\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Resources entities.
 *
 * @ingroup resources
 */
interface ResourcesInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Resources name.
   *
   * @return string
   *   Name of the Resources.
   */
  public function getName();

  /**
   * Sets the Resources name.
   *
   * @param string $name
   *   The Resources name.
   *
   * @return \Drupal\resources\Entity\ResourcesInterface
   *   The called Resources entity.
   */
  public function setName($name);

  /**
   * Gets the Resources creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Resources.
   */
  public function getCreatedTime();

  /**
   * Sets the Resources creation timestamp.
   *
   * @param int $timestamp
   *   The Resources creation timestamp.
   *
   * @return \Drupal\resources\Entity\ResourcesInterface
   *   The called Resources entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Resources published status indicator.
   *
   * Unpublished Resources are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Resources is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Resources.
   *
   * @param bool $published
   *   TRUE to set this Resources to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\resources\Entity\ResourcesInterface
   *   The called Resources entity.
   */
  public function setPublished($published);

  /**
   * Gets the Resources revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Resources revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\resources\Entity\ResourcesInterface
   *   The called Resources entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Resources revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Resources revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\resources\Entity\ResourcesInterface
   *   The called Resources entity.
   */
  public function setRevisionUserId($uid);

}
