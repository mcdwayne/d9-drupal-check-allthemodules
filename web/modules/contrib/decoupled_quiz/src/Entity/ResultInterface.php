<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Result entities.
 *
 * @ingroup decoupled_quiz
 */
interface ResultInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Result name.
   *
   * @return string
   *   Name of the Result.
   */
  public function getName();

  /**
   * Sets the Result name.
   *
   * @param string $name
   *   The Result name.
   *
   * @return \Drupal\decoupled_quiz\Entity\ResultInterface
   *   The called Result entity.
   */
  public function setName($name);

  /**
   * Gets the Result creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Result.
   */
  public function getCreatedTime();

  /**
   * Sets the Result creation timestamp.
   *
   * @param int $timestamp
   *   The Result creation timestamp.
   *
   * @return \Drupal\decoupled_quiz\Entity\ResultInterface
   *   The called Result entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Result published status indicator.
   *
   * Unpublished Result are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Result is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Result.
   *
   * @param bool $published
   *   TRUE to set this Result to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\decoupled_quiz\Entity\ResultInterface
   *   The called Result entity.
   */
  public function setPublished($published);

  /**
   * Gets the Result revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Result revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\decoupled_quiz\Entity\ResultInterface
   *   The called Result entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Result revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Result revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\decoupled_quiz\Entity\ResultInterface
   *   The called Result entity.
   */
  public function setRevisionUserId($uid);

}
