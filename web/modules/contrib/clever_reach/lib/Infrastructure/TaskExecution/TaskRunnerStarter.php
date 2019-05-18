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
 * Class TaskRunnerStarter
 *
 * @package CleverReach\Infrastructure\TaskExecution
 */
class TaskRunnerStarter implements Runnable
{
    /**
     * Unique runner guid.
     *
     * @var string
     */
    private $guid;
    /**
     * Instance of task runner status storage.
     *
     * @var TaskRunnerStatusStorageInterface
     */
    private $runnerStatusStorage;
    /**
     * Instance of task runner.
     *
     * @var TaskRunner
     */
    private $taskRunner;
    /**
     * Instance of task runner wakeup service.
     *
     * @var TaskRunnerWakeupInterface
     */
    private $taskWakeup;

    /**
     * TaskRunnerStarter constructor.
     *
     * @param string $guid Unique runner guid.
     */
    public function __construct($guid)
    {
        $this->guid = $guid;
    }

    /**
     * String representation of object.
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize(array($this->guid));
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->guid) = unserialize($serialized);
    }

    /**
     * Get unique runner guid.
     *
     * @return string
     *   Unique runner string.
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Starts synchronously currently active task runner instance
     */
    public function run()
    {
        try {
            $this->doRun();
        } catch (TaskRunnerStatusStorageUnavailableException $ex) {
            Logger::logError(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Runner status storage unavailable.',
                    'ExceptionMessage' => $ex->getMessage(),
                ))
            );
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Runner status storage unavailable.',
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        } catch (TaskRunnerRunException $ex) {
            Logger::logInfo(
                json_encode(array(
                    'Message' => $ex->getMessage(),
                    'ExceptionMessage' => $ex->getMessage(),
                ))
            );
            Logger::logDebug(
                json_encode(array(
                    'Message' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        } catch (\Exception $ex) {
            Logger::logError(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Unexpected error occurred.',
                    'ExceptionMessage' => $ex->getMessage(),
                ))
            );
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Unexpected error occurred.',
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        }
    }

    /**
     * Run task runner.
     *
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function doRun()
    {
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
     * Gets task runner status storage instance.
     *
     * @return TaskRunnerStatusStorageInterface
     *   Instance of runner status storage service.
     */
    private function getRunnerStorage()
    {
        if ($this->runnerStatusStorage === null) {
            $this->runnerStatusStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
        }

        return $this->runnerStatusStorage;
    }

    /**
     * Gets task runner instance.
     *
     * @return TaskRunner
     *   Instance of runner service.
     */
    private function getTaskRunner()
    {
        if ($this->taskRunner === null) {
            $this->taskRunner = ServiceRegister::getService(TaskRunner::CLASS_NAME);
        }

        return $this->taskRunner;
    }

    /**
     * Gets task runner wakeup instance.
     *
     * @return TaskRunnerWakeupInterface
     *   Instance of runner wakeup service.
     */
    private function getTaskWakeup()
    {
        if ($this->taskWakeup === null) {
            $this->taskWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
        }

        return $this->taskWakeup;
    }
}
