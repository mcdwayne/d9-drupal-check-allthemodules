<?php

namespace Drupal\new_relic_rpm;

/**
 * Provides helpers to use timers throughout a request.
 */
class Timer {

  static protected $timers = [];

  /**
   * Starts the timer with the specified name.
   *
   * @param string $name
   *   The name of the timer.
   */
  public static function start($name) {
    static::$timers[$name] = microtime(TRUE);
  }

  /**
   * Stops the timer with the specified name and returns the time.
   *
   * @param string $name
   *   The name of the timer.
   *
   * @return int
   *   The time since it was started in ms.
   */
  public static function stop($name) {
    if (isset(static::$timers[$name])) {
      $stop = microtime(TRUE);
      $diff = round(($stop - static::$timers[$name]) * 1000, 2);
      return $diff;
    }
  }

}
