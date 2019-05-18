<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 * Class TaskRunnerStatusStorage
 *
 * @package CleverReach\Infrastructure\TaskExecution
 */
class TaskRunnerStatusStorage implements TaskRunnerStatusStorageInterface
{
    /**
     * Instance of configuration service.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * Gets current task runner status.
     *
     * @return TaskRunnerStatus
     *   Current runner status.
     * @throws TaskRunnerStatusStorageUnavailableException
     *   When task storage is not available.
     */
    public function getStatus()
    {
        $result = $this->getConfigService()->getTaskRunnerStatus();
        if (empty($result)) {
            throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available');
        }

        return new TaskRunnerStatus($result['guid'], $result['timestamp']);
    }

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
    public function setStatus(TaskRunnerStatus $status)
    {
        $this->checkTaskRunnerStatusChangeAvailability($status);
        $this->getConfigService()->setTaskRunnerStatus($status->getGuid(), $status->getAliveSinceTimestamp());
    }

    /**
     * Validates if task runner status can be changed.
     *
     * @param TaskRunnerStatus $status Status to be set.
     *
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    private function checkTaskRunnerStatusChangeAvailability(TaskRunnerStatus $status)
    {
        $currentGuid = $this->getStatus()->getGuid();
        $guidForUpdate = $status->getGuid();

        if (!empty($currentGuid) && !empty($guidForUpdate) && $currentGuid !== $guidForUpdate) {
            throw new TaskRunnerStatusChangeException(
                'Task runner with guid: ' . $guidForUpdate . ' can not change the status.'
            );
        }
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration
     *   Instance of configuration service.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
