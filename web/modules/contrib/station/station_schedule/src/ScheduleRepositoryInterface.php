<?php

namespace Drupal\station_schedule;

/**
 * @todo.
 */
interface ScheduleRepositoryInterface {

  /**
   * @return \Drupal\station_schedule\ScheduleInterface|null
   */
  public function getCurrentSchedule();

  /**
   * @return int
   */
  public function getCurrentScheduleId();

  /**
   * @return \Drupal\station_schedule\ScheduleItemInterface|null
   */
  public function getCurrentScheduleItem();

  /**
   * @return \Drupal\station_schedule\ScheduleItemInterface|null
   */
  public function getNextScheduleItem();

}
