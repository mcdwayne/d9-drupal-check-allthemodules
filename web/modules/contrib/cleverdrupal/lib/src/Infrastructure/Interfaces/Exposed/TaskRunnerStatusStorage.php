<?php

namespace CleverReach\Infrastructure\Interfaces\Exposed;

use CleverReach\Infrastructure\TaskExecution\TaskRunnerStatus;

/**
 *
 */
interface TaskRunnerStatusStorage {
  const CLASS_NAME = __CLASS__;

  /**
   * Gets current task runner status.
   *
   * @return \CleverReach\Infrastructure\TaskExecution\TaskRunnerStatus Current runner status
   *
   * @throws TaskRunnerStatusStorageUnavailableException When task storage is not available
   */
  public function getStatus();

  /**
   * Sets status of task runner to provided status.
   * Setting new task status to nonempty guid must fail if currently set guid is not empty.
   *
   * @param \CleverReach\Infrastructure\TaskExecution\TaskRunnerStatus $status
   *
   * @throws TaskRunnerStatusChangeException Thrown when setting status operation fails because:
   *      - Trying to set new task status to new nonempty guid but currently set guid is not empty
   * @throws TaskRunnerStatusStorageUnavailableException When task storage is not available
   */
  public function setStatus(TaskRunnerStatus $status);

}
