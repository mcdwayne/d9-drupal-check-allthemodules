<?php

namespace CleverReach\Infrastructure\Interfaces\Exposed;

use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerStatus;

/**
 * Interface TaskRunnerStatusStorage
 *
 * @package CleverReach\Infrastructure\Interfaces\Exposed
 */
interface TaskRunnerStatusStorage
{
    const CLASS_NAME = __CLASS__;

    /**
     * Gets current task runner status.
     *
     * @return TaskRunnerStatus
     *   Current runner status.
     * @throws TaskRunnerStatusStorageUnavailableException
     *   When task storage is not available.
     */
    public function getStatus();

    /**
     * Sets status of task runner to provided status.
     *
     * Setting new task status to nonempty guid must fail if currently set guid is not empty.
     *
     * @param TaskRunnerStatus $status Current task status.
     *
     * @throws TaskRunnerStatusChangeException
     *   Trying to set new task status to new nonempty guid but currently set guid is not empty.
     * @throws TaskRunnerStatusStorageUnavailableException
     *   When task storage is not available.
     */
    public function setStatus(TaskRunnerStatus $status);
}
