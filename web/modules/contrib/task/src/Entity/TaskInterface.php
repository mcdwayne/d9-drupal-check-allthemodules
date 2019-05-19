<?php

namespace Drupal\task\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Task entities.
 *
 * @ingroup task
 */
interface TaskInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Task name.
   *
   * @return string
   *   Name of the Task.
   */
  public function getName();

  /**
   * Sets the Task name.
   *
   * @param string $name
   *   The Task name.
   *
   * @return \Drupal\task\Entity\TaskInterface
   *   The called Task entity.
   */
  public function setName($name);

  /**
   * Gets the Task creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Task.
   */
  public function getCreatedTime();

  /**
   * Sets the Task creation timestamp.
   *
   * @param int $timestamp
   *   The Task creation timestamp.
   *
   * @return \Drupal\task\Entity\TaskInterface
   *   The called Task entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Task published status indicator.
   *
   * Unpublished Task are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Task is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Task.
   *
   * @param bool $published
   *   TRUE to set this Task to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\task\Entity\TaskInterface
   *   The called Task entity.
   */
  public function setPublished($published);

  /**
   * Gets the Task revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Task revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\task\Entity\TaskInterface
   *   The called Task entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Task revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Task revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\task\Entity\TaskInterface
   *   The called Task entity.
   */
  public function setRevisionUserId($uid);

}
