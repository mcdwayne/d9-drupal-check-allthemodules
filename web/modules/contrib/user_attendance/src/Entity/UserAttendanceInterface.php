<?php

namespace Drupal\user_attendance\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User attendance entities.
 *
 * @ingroup user_attendance
 */
interface UserAttendanceInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the User attendance type.
   *
   * @return string
   *   The User attendance type.
   */
  public function getType();

  /**
   * Gets the User attendance name.
   *
   * @return string
   *   Name of the User attendance.
   */
  public function getName();

  /**
   * Sets the User attendance name.
   *
   * @param string $name
   *   The User attendance name.
   *
   * @return \Drupal\user_attendance\Entity\UserAttendanceInterface
   *   The called User attendance entity.
   */
  public function setName($name);

  /**
   * Gets the User attendance start timestamp.
   *
   * @return int
   *   Start timestamp of the User attendance.
   */
  public function getStartTime();

  /**
   * Sets the User attendance start timestamp.
   *
   * @param int $timestamp
   *   The User attendance start timestamp.
   *
   * @return \Drupal\user_attendance\Entity\UserAttendanceInterface
   *   The called User attendance entity.
   */
  public function setStartTime($timestamp);

  /**
   * Gets the User attendance end timestamp.
   *
   * @return int
   *   End timestamp of the User attendance.
   */
  public function getEndTime();

  /**
   * Sets the User attendance end timestamp.
   *
   * @param int $timestamp
   *   The User attendance end timestamp.
   *
   * @return \Drupal\user_attendance\Entity\UserAttendanceInterface
   *   The called User attendance entity.
   */
  public function setEndTime($timestamp);

  /**
   * Gets the User attendance creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User attendance.
   */
  public function getCreatedTime();

  /**
   * Sets the User attendance creation timestamp.
   *
   * @param int $timestamp
   *   The User attendance creation timestamp.
   *
   * @return \Drupal\user_attendance\Entity\UserAttendanceInterface
   *   The called User attendance entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the User attendance published status indicator.
   *
   * Unpublished User attendance are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the User attendance is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a User attendance.
   *
   * @param bool $published
   *   TRUE to set this User attendance to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\user_attendance\Entity\UserAttendanceInterface
   *   The called User attendance entity.
   */
  public function setPublished($published);

}
