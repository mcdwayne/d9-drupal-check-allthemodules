<?php

namespace Drupal\task_template\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Task Template entities.
 *
 * @ingroup task_template
 */
interface TaskTemplateInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Task Template name.
   *
   * @return string
   *   Name of the Task Template.
   */
  public function getName();

  /**
   * Sets the Task Template name.
   *
   * @param string $name
   *   The Task Template name.
   *
   * @return \Drupal\task_template\Entity\TaskTemplateInterface
   *   The called Task Template entity.
   */
  public function setName($name);

  /**
   * Gets the Task Template creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Task Template.
   */
  public function getCreatedTime();

  /**
   * Sets the Task Template creation timestamp.
   *
   * @param int $timestamp
   *   The Task Template creation timestamp.
   *
   * @return \Drupal\task_template\Entity\TaskTemplateInterface
   *   The called Task Template entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Task Template published status indicator.
   *
   * Unpublished Task Template are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Task Template is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Task Template.
   *
   * @param bool $published
   *   TRUE to set this Task Template to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\task_template\Entity\TaskTemplateInterface
   *   The called Task Template entity.
   */
  public function setPublished($published);

  /**
   * Gets the Task Template revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Task Template revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\task_template\Entity\TaskTemplateInterface
   *   The called Task Template entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Task Template revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Task Template revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\task_template\Entity\TaskTemplateInterface
   *   The called Task Template entity.
   */
  public function setRevisionUserId($uid);

}
