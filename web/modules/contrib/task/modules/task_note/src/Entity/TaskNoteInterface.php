<?php

namespace Drupal\task_note\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Task Note entities.
 *
 * @ingroup task_note
 */
interface TaskNoteInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Task Note name.
   *
   * @return string
   *   Name of the Task Note.
   */
  public function getName();

  /**
   * Sets the Task Note name.
   *
   * @param string $name
   *   The Task Note name.
   *
   * @return \Drupal\task_note\Entity\TaskNoteInterface
   *   The called Task Note entity.
   */
  public function setName($name);

  /**
   * Gets the Task Note creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Task Note.
   */
  public function getCreatedTime();

  /**
   * Sets the Task Note creation timestamp.
   *
   * @param int $timestamp
   *   The Task Note creation timestamp.
   *
   * @return \Drupal\task_note\Entity\TaskNoteInterface
   *   The called Task Note entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Task Note published status indicator.
   *
   * Unpublished Task Note are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Task Note is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Task Note.
   *
   * @param bool $published
   *   TRUE to set this Task Note to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\task_note\Entity\TaskNoteInterface
   *   The called Task Note entity.
   */
  public function setPublished($published);

  /**
   * Gets the Task Note revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Task Note revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\task_note\Entity\TaskNoteInterface
   *   The called Task Note entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Task Note revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Task Note revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\task_note\Entity\TaskNoteInterface
   *   The called Task Note entity.
   */
  public function setRevisionUserId($uid);

}
