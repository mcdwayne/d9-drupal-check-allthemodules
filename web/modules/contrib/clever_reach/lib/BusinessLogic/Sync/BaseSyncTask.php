<?php

namespace CleverReach\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Task;

/**
 * Class BaseSyncTask
 *
 * @package CleverReach\BusinessLogic\Sync
 */
abstract class BaseSyncTask extends Task
{
    /**
     * Instance of proxy class.
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * Gets proxy class instance.
     *
     * @return \CleverReach\BusinessLogic\Proxy
     *   Instance of proxy class.
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}
