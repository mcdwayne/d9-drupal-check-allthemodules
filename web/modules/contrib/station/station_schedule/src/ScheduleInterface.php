<?php

/**
 * @file
 * Contains \Drupal\station_schedule\ScheduleInterface.
 */

namespace Drupal\station_schedule;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * @todo.
 */
interface ScheduleInterface extends ContentEntityInterface {
  public function getStartHour();
  public function getEndHour();
  public function getIncrement();
  public function getUnscheduledMessage();

  /**
   * @return \Drupal\station_schedule\ScheduleItemInterface[]
   */
  public function getScheduledItems();

  /**
   * @return \Drupal\station_schedule\ScheduleItemInterface[][]
   */
  public function getScheduledItemsByDay();

}
