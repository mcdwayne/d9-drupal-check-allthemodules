<?php

namespace CleverReach\Infrastructure\Interfaces;

use CleverReach\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter
 *
 * @package CleverReach\Infrastructure\Interfaces
 */
interface LoggerAdapter
{
    /**
     * Log message in the system.
     *
     * @param LogData|null $data Log data object.
     */
    public function logMessage($data);
}
