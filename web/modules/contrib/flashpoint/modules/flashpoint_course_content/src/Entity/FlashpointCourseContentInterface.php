<?php

namespace Drupal\flashpoint_course_content\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Flashpoint course content entities.
 *
 * @ingroup flashpoint_course_content
 */
interface FlashpointCourseContentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Flashpoint course content name.
   *
   * @return string
   *   Name of the Flashpoint course content.
   */
  public function getName();

  /**
   * Sets the Flashpoint course content name.
   *
   * @param string $name
   *   The Flashpoint course content name.
   *
   * @return \Drupal\flashpoint_course_content\Entity\FlashpointCourseContentInterface
   *   The called Flashpoint course content entity.
   */
  public function setName($name);

  /**
   * Gets the Flashpoint course content creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Flashpoint course content.
   */
  public function getCreatedTime();

  /**
   * Sets the Flashpoint course content creation timestamp.
   *
   * @param int $timestamp
   *   The Flashpoint course content creation timestamp.
   *
   * @return \Drupal\flashpoint_course_content\Entity\FlashpointCourseContentInterface
   *   The called Flashpoint course content entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Flashpoint course content published status indicator.
   *
   * Unpublished Flashpoint course content are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Flashpoint course content is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Flashpoint course content.
   *
   * @param bool $published
   *   TRUE to set this Flashpoint course content to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\flashpoint_course_content\Entity\FlashpointCourseContentInterface
   *   The called Flashpoint course content entity.
   */
  public function setPublished($published);

}
