<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 *
 */
class TaskRunner {
  const CLASS_NAME = __CLASS__;

  /**
 * Automatic task runner wakeup delay in seconds .*/
  const WAKEUP_DELAY = 5;

  /**
   * @var stringRunnerguid*/
  protected $guid;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter*/
  private $asyncProcessStarter;

  /**
   * @var Queue*/
  private $queue;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage*/
  private $runnerStorage;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration*/
  private $configurationService;

  /**
   * @var \CleverReach\Infrastructure\Utility\TimeProvider*/
  private $timeProvider;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup*/
  private $taskWakeup;

  /**
   * Sets task runner guid.
   *
   * @param string $guid
   *   Runner guid to set.
   */
  public function setGuid($guid) {
    $this->guid = $guid;
  }

  /**
   * Starts task runner lifecycle.
   */
  public function run() {
    try {
      $this->logDebug(['Message' => 'Task runner: lifecycle started.']);

      if ($this->isCurrentRunnerAlive()) {
        $this->failOrRequeueExpiredTasks();
        $this->startOldestQueuedItems();
      }

      $this->wakeup();

      $this->logDebug(['Message' => 'Task runner: lifecycle ended.']);
    }
    catch (\Exception $ex) {
      $this->logDebug([
        'Message' => 'Fail to run task runner. Unexpected error occurred.',
        'ExceptionMessage' => $ex->getMessage(),
        'ExceptionTrace' => $ex->getTraceAsString(),
      ]);
    }
  }

  /**
   * @throws QueueItemDeserializationException
   * @throws QueueStorageUnavailableException
   * @throws TaskRunnerStatusStorageUnavailableException
   */
  private function failOrRequeueExpiredTasks() {
    $this->logDebug(['Message' => 'Task runner: expired tasks cleanup started.']);

    $runningItems = $this->getQueue()->findRunningItems();
    if (!$this->isCurrentRunnerAlive()) {
      return;
    }

    foreach ($runningItems as $runningItem) {
      if ($this->isItemExpired($runningItem) && $this->isCurrentRunnerAlive()) {
        $this->logMessageFor($runningItem, 'Task runner: Expired task detected.');
        $this->getConfigurationService()->setContext($runningItem->getContext());
        if ($runningItem->getProgressBasePoints() > $runningItem->getLastExecutionProgressBasePoints()) {
          $this->logMessageFor($runningItem, 'Task runner: Task requeue for execution continuation.');
          $this->getQueue()->requeue($runningItem);
        }
        else {
          $runningItem->reconfigureTask();
          $this->getQueue()->fail(
                $runningItem,
                sprintf('Task %s failed due to extended inactivity period.', $this->getItemDescription($runningItem))
            );
        }
      }
    }
  }

  /**
   * Starts oldest queue item from all queues respecting following list of criteria:
   *      - Queue must be without already running queue items
   *      - For one queue only one (oldest queued) item should be started
   *      - Number of running tasks must NOT be greater than maximal allowed by integration configuration.
   *
   * @throws ProcessStarterSaveException
   * @throws TaskRunnerStatusStorageUnavailableException
   * @throws QueueItemDeserializationException
   */
  private function startOldestQueuedItems() {
    $this->logDebug(['Message' => 'Task runner: available task detection started.']);

    // Calculate how many queue items can be started.
    $maxRunningTasks = $this->getConfigurationService()->getMaxStartedTasksLimit();
    $alreadyRunningItems = $this->getQueue()->findRunningItems();
    $numberOfAvailableSlotsForTaskRunning = $maxRunningTasks - count($alreadyRunningItems);
    if ($numberOfAvailableSlotsForTaskRunning <= 0) {
      $this->logDebug(['Message' => 'Task runner: max number of active tasks reached.']);
      return;
    }

    $items = $this->getQueue()->findOldestQueuedItems($numberOfAvailableSlotsForTaskRunning);

    if (!$this->isCurrentRunnerAlive()) {
      return;
    }

    foreach ($items as $item) {
      if (!$this->isCurrentRunnerAlive()) {
        return;
      }

      $this->logMessageFor($item, 'Task runner: Starting async task execution.');
      $this->getAsyncProcessStarter()->start(new QueueItemStarter($item->getId()));
    }
  }

  /**
   * @throws TaskRunnerStatusChangeException
   * @throws TaskRunnerStatusStorageUnavailableException
   */
  private function wakeup() {
    $this->logDebug(['Message' => 'Task runner: starting self deactivation.']);
    $this->getTimeProvider()->sleep($this->getWakeupDelay());

    $this->getRunnerStorage()->setStatus(TaskRunnerStatus::createNullStatus());

    $this->logDebug(['Message' => 'Task runner: sending task runner wakeup signal.']);
    $this->getTaskWakeup()->wakeup();
  }

  /**
   * @return bool
   * @throws TaskRunnerStatusStorageUnavailableException
   */
  private function isCurrentRunnerAlive() {
    $runnerStatus = $this->getRunnerStorage()->getStatus();
    $runnerExpired = $runnerStatus->isExpired();
    $runnerGuidIsCorrect = $this->guid === $runnerStatus->getGuid();

    if ($runnerExpired) {
      $this->logWarning(['Message' => 'Task runner: Task runner started but it is expired.']);
    }

    if (!$runnerGuidIsCorrect) {
      $this->logWarning(['Message' => 'Task runner: Task runner started but it is not active anymore.']);
    }

    return !$runnerExpired && $runnerGuidIsCorrect;
  }

  /**
   * @param QueueItem $item
   *
   * @return bool
   * @throws QueueItemDeserializationException
   */
  private function isItemExpired(QueueItem $item) {
    $currentTimestamp = $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp();
    $maxTaskInactivityPeriod = $item->getTask()->getMaxInactivityPeriod();

    return ($item->getLastUpdateTimestamp() + $maxTaskInactivityPeriod) < $currentTimestamp;
  }

  /**
   * @param QueueItem $item
   *
   * @return string
   * @throws QueueItemDeserializationException
   */
  private function getItemDescription(QueueItem $item) {
    return "{$item->getId()}({$item->getTaskType()})";
  }

  /**
   * @return \CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter
   */
  private function getAsyncProcessStarter() {
    if (empty($this->asyncProcessStarter)) {
      $this->asyncProcessStarter = ServiceRegister::getService(AsyncProcessStarter::CLASS_NAME);
    }

    return $this->asyncProcessStarter;
  }

  /**
   * @return Queue
   */
  private function getQueue() {
    if (empty($this->queue)) {
      $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
    }

    return $this->queue;
  }

  /**
   *
   */
  private function getRunnerStorage() {
    if (empty($this->runnerStorage)) {
      $this->runnerStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
    }

    return $this->runnerStorage;
  }

  /**
   * @return \CleverReach\Infrastructure\Interfaces\Required\Configuration
   */
  private function getConfigurationService() {
    if (empty($this->configurationService)) {
      $this->configurationService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    return $this->configurationService;
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
  private function getTaskWakeup() {
    if (empty($this->taskWakeup)) {
      $this->taskWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
    }

    return $this->taskWakeup;
  }

  /**
   *
   */
  private function getWakeupDelay() {
    $configurationValue = $this->getConfigurationService()->getTaskRunnerWakeupDelay();
    return !is_null($configurationValue) ? $configurationValue : self::WAKEUP_DELAY;
  }

  /**
   * Logs message and queue item details.
   *
   * @param QueueItem $queueItem
   * @param string $message
   *
   * @throws QueueItemDeserializationException
   */
  private function logMessageFor(QueueItem $queueItem, $message) {
    $this->logDebug([
      'RunnerGuid' => $this->guid,
      'Message' => $message,
      'TaskId' => $queueItem->getId(),
      'TaskType' => $queueItem->getTaskType(),
      'TaskRetries' => $queueItem->getRetries(),
      'TaskProgressBasePoints' => $queueItem->getProgressBasePoints(),
      'TaskLastExecutionProgressBasePoints' => $queueItem->getLastExecutionProgressBasePoints(),
    ]);
  }

  /**
   *
   */
  private function logDebug(array $debugContent) {
    $debugContent['RunnerGuid'] = $this->guid;
    Logger::logDebug(json_encode($debugContent));
  }

  /**
   *
   */
  private function logWarning(array $debugContent) {
    $debugContent['RunnerGuid'] = $this->guid;
    Logger::logWarning(json_encode($debugContent));
  }

}
