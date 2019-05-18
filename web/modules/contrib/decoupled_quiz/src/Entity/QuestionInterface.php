<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Question entities.
 *
 * @ingroup decoupled_quiz
 */
interface QuestionInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Question name.
   *
   * @return string
   *   Name of the Question.
   */
  public function getName();

  /**
   * Sets the Question name.
   *
   * @param string $name
   *   The Question name.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setName($name);

  /**
   * Gets the Question creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Question.
   */
  public function getCreatedTime();

  /**
   * Sets the Question creation timestamp.
   *
   * @param int $timestamp
   *   The Question creation timestamp.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Question published status indicator.
   *
   * Unpublished Question are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Question is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Question.
   *
   * @param bool $published
   *   TRUE to set this Question to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setPublished($published);

  /**
   * Gets the Question revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Question revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Question revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Question revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuestionInterface
   *   The called Question entity.
   */
  public function setRevisionUserId($uid);

}
