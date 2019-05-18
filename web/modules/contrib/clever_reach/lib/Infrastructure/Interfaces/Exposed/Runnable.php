<?php

namespace CleverReach\Infrastructure\Interfaces\Exposed;

/**
 * Interface Runnable
 *
 * @package CleverReach\Infrastructure\Interfaces\Exposed
 */
interface Runnable extends \Serializable
{
    /**
     * Starts runnable run logic.
     */
    public function run();
}
