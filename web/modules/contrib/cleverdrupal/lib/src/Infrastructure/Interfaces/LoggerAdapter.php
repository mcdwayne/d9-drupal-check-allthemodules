<?php

namespace CleverReach\Infrastructure\Interfaces;

/**
 *
 */
interface LoggerAdapter {

  /**
   * Log message in system.
   *
   * @param \CleverReach\Infrastructure\Logger\LogData $data
   */
  public function logMessage($data);

}
