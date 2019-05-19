<?php

/**
 * @file
 * Contains \Drupal\station_schedule\ScheduleItemInterface.
 */

namespace Drupal\station_schedule;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * @todo.
 */
interface ScheduleItemInterface extends ContentEntityInterface {

  /**
   * @return int
   */
  public function getStart();

  /**
   * @return int
   */
  public function getFinish();

  /**
   * @return \Drupal\station_schedule\ScheduleInterface
   */
  public function getSchedule();

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getProgram();

  /**
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public function getDjs();

}
