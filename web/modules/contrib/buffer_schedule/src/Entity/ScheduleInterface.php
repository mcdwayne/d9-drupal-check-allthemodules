<?php

namespace Drupal\buffer_schedule\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Schedule entities.
 *
 * @ingroup buffer_schedule
 */
interface ScheduleInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Schedule name.
   *
   * @return string
   *   Name of the Schedule.
   */
  public function getName();

  /**
   * Sets the Schedule name.
   *
   * @param string $name
   *   The Schedule name.
   *
   * @return \Drupal\buffer_schedule\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setName($name);

  /**
   * Gets the Schedule creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Schedule.
   */
  public function getCreatedTime();

  /**
   * Sets the Schedule creation timestamp.
   *
   * @param int $timestamp
   *   The Schedule creation timestamp.
   *
   * @return \Drupal\buffer_schedule\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Schedule published status indicator.
   *
   * Unpublished Schedule are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Schedule is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Schedule.
   *
   * @param bool $published
   *   TRUE to set this Schedule to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\buffer_schedule\Entity\ScheduleInterface
   *   The called Schedule entity.
   */
  public function setPublished($published);

}
