<?php

namespace Drupal\flashpoint_course_module\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Course module entities.
 *
 * @ingroup flashpoint_course_module
 */
interface FlashpointCourseModuleInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Flashpoint Course module name.
   *
   * @return string
   *   Name of the Flashpoint Course module.
   */
  public function getName();

  /**
   * Sets the Course module name.
   *
   * @param string $name
   *   The Course module name.
   *
   * @return \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface
   *   The called Course module entity.
   */
  public function setName($name);

  /**
   * Gets the Course module creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Course module.
   */
  public function getCreatedTime();

  /**
   * Sets the Course module creation timestamp.
   *
   * @param int $timestamp
   *   The Course module creation timestamp.
   *
   * @return \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface
   *   The called Course module entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Course module published status indicator.
   *
   * Unpublished Course module are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Course module is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Course module.
   *
   * @param bool $published
   *   TRUE to set this Course module to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface
   *   The called Course module entity.
   */
  public function setPublished($published);

  /**
   * Gets the Course module revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Course module revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface
   *   The called Course module entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Course module revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Course module revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleInterface
   *   The called Course module entity.
   */
  public function setRevisionUserId($uid);

}
