<?php

namespace Drupal\quizard;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Quiz results entities.
 *
 * @ingroup quizard
 */
interface QuizResultsInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {
  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Quiz results name.
   *
   * @return string
   *   Name of the Quiz results.
   */
  public function getName();

  /**
   * Sets the Quiz results name.
   *
   * @param string $name
   *   The Quiz results name.
   *
   * @return \Drupal\quizard\QuizResultsInterface
   *   The called Quiz results entity.
   */
  public function setName($name);

  /**
   * Gets the Quiz results creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Quiz results.
   */
  public function getCreatedTime();

  /**
   * Sets the Quiz results creation timestamp.
   *
   * @param int $timestamp
   *   The Quiz results creation timestamp.
   *
   * @return \Drupal\quizard\QuizResultsInterface
   *   The called Quiz results entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Quiz results published status indicator.
   *
   * Unpublished Quiz results are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Quiz results is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Quiz results.
   *
   * @param bool $published
   *   TRUE to set this Quiz results to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\quizard\QuizResultsInterface
   *   The called Quiz results entity.
   */
  public function setPublished($published);

}
