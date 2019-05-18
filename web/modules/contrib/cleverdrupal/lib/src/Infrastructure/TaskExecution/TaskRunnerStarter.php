<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 *
 */
class TaskRunnerStarter implements Runnable {
  /**
   * @var string*/
  private $guid;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage*/
  private $runnerStatusStorage;

  /**
   * @var TaskRunner*/
  private $taskRunner;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup*/
  private $taskWakeup;

  /**
   *
   */
  public function __construct($guid) {
    $this->guid = $guid;
  }

  /**
   * String representation of object.
   * @link http://php.net/manual/en/serializable.serialize.php
   *
   * @return string the string representation of the object or null
   *
   * @since 5.1.0
   */
  public function serialize() {
    return serialize([$this->guid]);
  }

  /**
   * Constructs the object.
   * @link http://php.net/manual/en/serializable.unserialize.php
   *
   * @param string $serialized
   *   <p>
   *   The string representation of the object.
   *   </p>.
   *
   * @return void
   *
   * @since 5.1.0
   */
  public function unserialize($serialized) {
    list($this->guid) = unserialize($serialized);
  }

  /**
   *
   */
  public function getGuid() {
    return $this->guid;
  }

  /**
   * Starts synchronously currently active task runner instance.
   *
   * @throws TaskRunnerRunException
   */
  public function run() {
    try {
      $this->doRun();
    }
    catch (TaskRunnerStatusStorageUnavailableException $ex) {
      Logger::logError(
        json_encode([
          'Message' => 'Failed to run task runner. Runner status storage unavailable.',
          'ExceptionMessage' => $ex->getMessage(),
        ])
        );
      Logger::logDebug(
            json_encode([
              'Message' => 'Failed to run task runner. Runner status storage unavailable.',
              'ExceptionMessage' => $ex->getMessage(),
              'ExceptionTrace' => $ex->getTraceAsString(),
            ])
        );
    }
    catch (TaskRunnerRunException $ex) {
      Logger::logInfo(
        json_encode([
          'Message' => $ex->getMessage(),
          'ExceptionMessage' => $ex->getMessage(),
        ])
        );
      Logger::logDebug(
            json_encode([
              'Message' => $ex->getMessage(),
              'ExceptionTrace' => $ex->getTraceAsString(),
            ])
        );
    }
    catch (\Exception $ex) {
      Logger::logError(
        json_encode([
          'Message' => 'Failed to run task runner. Unexpected error occurred.',
          'ExceptionMessage' => $ex->getMessage(),
        ])
        );
      Logger::logDebug(
            json_encode([
              'Message' => 'Failed to run task runner. Unexpected error occurred.',
              'ExceptionMessage' => $ex->getMessage(),
              'ExceptionTrace' => $ex->getTraceAsString(),
            ])
        );
    }
  }

  /**
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
   */
  private function doRun() {
    $runnerStatus = $this->getRunnerStorage()->getStatus();
    if ($this->guid !== $runnerStatus->getGuid()) {
      throw new TaskRunnerRunException('Failed to run task runner. Runner guid is not set as active.');
    }

    if ($runnerStatus->isExpired()) {
      $this->getTaskWakeup()->wakeup();
      throw new TaskRunnerRunException('Failed to run task runner. Runner is expired.');
    }

    $this->getTaskRunner()->setGuid($this->guid);
    $this->getTaskRunner()->run();
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
  private function getTaskRunner() {
    if (empty($this->taskRunner)) {
      $this->taskRunner = ServiceRegister::getService(TaskRunner::CLASS_NAME);
    }

    return $this->taskRunner;
  }

  /**
   *
   */
  private function getTaskWakeup() {
    if (empty($this->taskWakeup)) {
      $this->taskWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
    }

    return $this->taskWakeup;
  }

}
