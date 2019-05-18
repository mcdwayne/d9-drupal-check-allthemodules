<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 *
 */
class TaskRunnerStatusStorage implements TaskRunnerStatusStorageInterface {

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private $configService;

  /**
   *
   */
  public function getStatus() {
    $result = $this->getConfigService()->getTaskRunnerStatus();
    if (empty($result)) {
      throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available');
    }

    $taskRunnerStatus = new TaskRunnerStatus($result['guid'], $result['timestamp']);

    return $taskRunnerStatus;
  }

  /**
   *
   */
  public function setStatus(TaskRunnerStatus $status) {
    $this->checkTaskRunnerStatusChangeAvailability($status);
    $this->getConfigService()->setTaskRunnerStatus($status->getGuid(), $status->getAliveSinceTimestamp());
  }

  /**
   * @param TaskRunnerStatus $status
   * @throws TaskRunnerStatusChangeException
   */
  private function checkTaskRunnerStatusChangeAvailability(TaskRunnerStatus $status) {
    $currentGuid = $this->getStatus()->getGuid();
    $guidForUpdate = $status->getGuid();

    if (!empty($currentGuid) && !empty($guidForUpdate) && $currentGuid !== $guidForUpdate) {
      throw new TaskRunnerStatusChangeException(
        'Task runner with guid: ' . $guidForUpdate . ' can not change the status.'
      );
    }
  }

  /**
   *
   */
  private function getConfigService() {
    if (empty($this->configService)) {
      $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    return $this->configService;
  }

}
