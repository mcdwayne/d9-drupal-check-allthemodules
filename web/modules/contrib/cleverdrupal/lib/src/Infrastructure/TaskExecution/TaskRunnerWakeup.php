<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use CleverReach\Infrastructure\Utility\GuidProvider;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 *
 */
class TaskRunnerWakeup implements TaskRunnerWakeupInterface {
  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter*/
  private $asyncProcessStarter;

  /**
   * @var TaskRunnerStatusStorage*/
  private $runnerStatusStorage;

  /**
   * @var \CleverReach\Infrastructure\Utility\TimeProvider*/
  private $timeProvider;

  /**
   * @var \CleverReach\Infrastructure\Utility\GuidProvider*/
  private $guidProvider;

  /**
   * Wakes up TaskRunner instance asynchronously if active instance is not already running.
   */
  public function wakeup() {
    try {
      $this->doWakeup();
    }
    catch (TaskRunnerStatusChangeException $ex) {
      Logger::logDebug(
        json_encode([
          'Message' => 'Fail to wakeup task runner. Runner status storage failed to set new active state.',
          'ExceptionMessage' => $ex->getMessage(),
          'ExceptionTrace' => $ex->getTraceAsString(),
        ])
        );
    }
    catch (TaskRunnerStatusStorageUnavailableException $ex) {
      Logger::logDebug(
        json_encode([
          'Message' => 'Fail to wakeup task runner. Runner status storage unavailable.',
          'ExceptionMessage' => $ex->getMessage(),
          'ExceptionTrace' => $ex->getTraceAsString(),
        ])
        );
    }
    catch (\Exception $ex) {
      Logger::logDebug(
        json_encode([
          'Message' => 'Fail to wakeup task runner. Unexpected error occurred.',
          'ExceptionMessage' => $ex->getMessage(),
          'ExceptionTrace' => $ex->getTraceAsString(),
        ])
        );
    }
  }

  /**
   *
   */
  private function doWakeup() {
    $runnerStatus = $this->getRunnerStorage()->getStatus();
    $currentGuid = $runnerStatus->getGuid();
    if (!empty($currentGuid) && !$runnerStatus->isExpired()) {
      return;
    }

    if ($runnerStatus->isExpired()) {
      $this->runnerStatusStorage->setStatus(TaskRunnerStatus::createNullStatus());
      Logger::logDebug('Expired task runner detected, wakeup component will start new instance.');
    }

    $guid = $this->getGuidProvider()->generateGuid();

    $this->runnerStatusStorage->setStatus(new TaskRunnerStatus(
        $guid,
        $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp()
    ));

    $this->getAsyncProcessStarter()->start(new TaskRunnerStarter($guid));
  }

  /**
   *
   */
  private function getRunnerStorage() {
    if (empty($this->runnerStatusStorage)) {
      $this->runnerStatusStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
    }

    return $this->runnerStatusStorage;
  }

  /**
   *
   */
  private function getGuidProvider() {
    if (empty($this->guidProvider)) {
      $this->guidProvider = ServiceRegister::getService(GuidProvider::CLASS_NAME);
    }

    return $this->guidProvider;
  }

  /**
   *
   */
  private function getTimeProvider() {
    if (empty($this->timeProvider)) {
      $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
    }

    return $this->timeProvider;
  }

  /**
   *
   */
  private function getAsyncProcessStarter() {
    if (empty($this->asyncProcessStarter)) {
      $this->asyncProcessStarter = ServiceRegister::getService(AsyncProcessStarter::CLASS_NAME);
    }

    return $this->asyncProcessStarter;
  }

}
