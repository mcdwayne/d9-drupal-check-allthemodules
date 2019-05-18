<?php

namespace CleverReach\Infrastructure\Utility;

/**
 *
 */
class TimeProvider {
  const CLASS_NAME = __CLASS__;

  /**
   * Gets current time in default server timezone.
   *
   * @return \DateTime Current time
   */
  public function getCurrentLocalTime() {
    return new \DateTime();
  }

  /**
   * Delays execution for sleep time seconds.
   *
   * @param int $sleepTime
   *   Sleep time in seconds.
   */
  public function sleep($sleepTime) {
    sleep($sleepTime);
  }

}
