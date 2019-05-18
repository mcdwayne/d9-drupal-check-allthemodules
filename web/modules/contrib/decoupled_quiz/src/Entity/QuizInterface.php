<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Quiz entities.
 *
 * @ingroup decoupled_quiz
 */
interface QuizInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Quiz name.
   *
   * @return string
   *   Name of the Quiz.
   */
  public function getName();

  /**
   * Sets the Quiz name.
   *
   * @param string $name
   *   The Quiz name.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setName($name);

  /**
   * Gets the Quiz creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Quiz.
   */
  public function getCreatedTime();

  /**
   * Sets the Quiz creation timestamp.
   *
   * @param int $timestamp
   *   The Quiz creation timestamp.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Quiz published status indicator.
   *
   * Unpublished Quiz are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Quiz is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Quiz.
   *
   * @param bool $published
   *   TRUE to set this Quiz to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setPublished($published);

  /**
   * Gets the Quiz revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Quiz revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Quiz revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Quiz revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\decoupled_quiz\Entity\QuizInterface
   *   The called Quiz entity.
   */
  public function setRevisionUserId($uid);

}
