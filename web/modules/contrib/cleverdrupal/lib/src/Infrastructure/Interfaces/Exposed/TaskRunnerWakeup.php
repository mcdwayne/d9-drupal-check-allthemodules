<?php

namespace CleverReach\Infrastructure\Interfaces\Exposed;

/**
 *
 */
interface TaskRunnerWakeup {
  const CLASS_NAME = __CLASS__;

  /**
   * Wakes up TaskRunner instance asynchronously if active instance is not already running.
   */
  public function wakeup();

}
