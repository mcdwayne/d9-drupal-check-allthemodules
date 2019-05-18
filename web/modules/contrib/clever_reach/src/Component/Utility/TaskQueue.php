<?php

namespace Drupal\clever_reach\Component\Utility;

use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\Task;

/**
 * TaskQueue helper class.
 */
class TaskQueue {

  /**
   * Enqueues a task to the queue.
   *
   * @param \CleverReach\Infrastructure\TaskExecution\Task $task
   *   Task that needs to be enqueued.
   * @param bool $throwException
   *   Indicates whether exception should be thrown or not.
   *
   *   Namespace for all availabe tasks in CleverReach core:.
   *
   * @see \CleverReach\BusinessLogic\Sync
   *
   * @throws QueueStorageUnavailableException
   */
  public static function enqueue(Task $task, $throwException = FALSE) {
    try {
      $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
      $accessToken = $configService->getAccessToken();
      if (!empty($accessToken)) {
        /** @var \CleverReach\Infrastructure\TaskExecution\Queue $queueService */
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        $queueService->enqueue($configService->getQueueName(), $task);
      }
    }
    catch (QueueStorageUnavailableException $ex) {
      Logger::logDebug(
        json_encode(
            [
              'Message' => 'Failed to enqueue task ' . $task->getType(),
              'ExceptionMessage' => $ex->getMessage(),
              'ExceptionTrace' => $ex->getTraceAsString(),
              'TaskData' => serialize($task),
            ]
        ),
        'Integration'
        );
      if ($throwException) {
        throw $ex;
      }
    }
  }

  /**
   * Calls the wakeup on task runner.
   */
  public static function wakeup() {
    /** @var \CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup $wakeupService */
    $wakeupService = ServiceRegister::getService(TaskRunnerWakeup::CLASS_NAME);
    $wakeupService->wakeup();
  }

}
