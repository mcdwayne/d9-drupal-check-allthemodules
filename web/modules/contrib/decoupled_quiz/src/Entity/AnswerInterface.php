<?php

namespace Drupal\decoupled_quiz\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Answer entities.
 *
 * @ingroup decoupled_quiz
 */
interface AnswerInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Answer name.
   *
   * @return string
   *   Name of the Answer.
   */
  public function getName();

  /**
   * Sets the Answer name.
   *
   * @param string $name
   *   The Answer name.
   *
   * @return \Drupal\decoupled_quiz\Entity\AnswerInterface
   *   The called Answer entity.
   */
  public function setName($name);

  /**
   * Gets the Answer creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Answer.
   */
  public function getCreatedTime();

  /**
   * Sets the Answer creation timestamp.
   *
   * @param int $timestamp
   *   The Answer creation timestamp.
   *
   * @return \Drupal\decoupled_quiz\Entity\AnswerInterface
   *   The called Answer entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Answer published status indicator.
   *
   * Unpublished Answer are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Answer is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Answer.
   *
   * @param bool $published
   *   TRUE to set this Answer to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\decoupled_quiz\Entity\AnswerInterface
   *   The called Answer entity.
   */
  public function setPublished($published);

  /**
   * Gets the Answer revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Answer revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\decoupled_quiz\Entity\AnswerInterface
   *   The called Answer entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Answer revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Answer revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\decoupled_quiz\Entity\AnswerInterface
   *   The called Answer entity.
   */
  public function setRevisionUserId($uid);

}
