<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 *
 */
class Queue {

  const CLASS_NAME = __CLASS__;

  /**
 * Maximum failure retries count .*/
  const MAX_RETRIES = 5;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage*/
  private $storage;

  /**
   * @var \CleverReach\Infrastructure\Utility\TimeProvider*/
  private $timeProvider;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup*/
  private $taskRunnerWakeup;

  /**
   * @var \CleverReach\Infrastructure\Interfaces\Required\Configuration*/
  private $configService;

  /**
   * Enqueues queue item to a given queue and stores changes.
   *
   * @param string $queueName
   *   Name of a queue where queue item should be queued.
   * @param Task $task
   *   Task to enqueue.
   * @param string $context
   *   Task execution context. If integration supports multiple accounts (middleware integration) context
   *   based on account id should be provided. Failing to do this will result in global task context and unpredictable task execution.
   *
   * @return \CleverReach\Infrastructure\TaskExecution\QueueItem Created queue item
   *
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
   */
  public function enqueue($queueName, Task $task, $context = '') {
    $queueItem = new QueueItem($task);
    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setQueueName($queueName);
    $queueItem->setContext($context);
    $queueItem->setQueueTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

    try {
      $this->save($queueItem);
      $this->getTaskRunnerWakeup()->wakeup();
    }
    catch (QueueItemSaveException $exception) {
      throw new QueueStorageUnavailableException('Unable to enqueue task. Queue storage failed to save item.', 0, $exception);
    }

    return $queueItem;
  }

  /**
   * Starts task execution, puts queue item in "in_progress" state and stores queue item changes.
   *
   * @param QueueItem $queueItem
   *   Queue item to start.
   *
   * @throws HttpAuthenticationException
   * @throws QueueStorageUnavailableException
   * @throws QueueItemDeserializationException
   */
  public function start(QueueItem $queueItem) {
    if ($queueItem->getStatus() !== QueueItem::QUEUED) {
      $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::IN_PROGRESS);
    }

    $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();

    $queueItem->setStatus(QueueItem::IN_PROGRESS);
    $queueItem->setStartTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
    $queueItem->setLastUpdateTimestamp($queueItem->getStartTimestamp());

    try {
      $this->save(
        $queueItem,
        ['status' => QueueItem::QUEUED, 'lastUpdateTimestamp' => $lastUpdateTimestamp]
      );

      $queueItem->getTask()->execute();
    }
    catch (HttpAuthenticationException $exception) {
      // Clear access token and user info.
      $this->getConfigService()->setAccessToken(NULL);
      $this->getConfigService()->setUserInfo(NULL);
      throw $exception;
    }
    catch (QueueItemSaveException $exception) {
      throw new QueueStorageUnavailableException('Unable to start task. Queue storage failed to save item.', 0, $exception);
    }
  }

  /**
   * Puts queue item in finished status and stores changes.
   *
   * @param QueueItem $queueItem
   *   Queue item to finish.
   *
   * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
   */
  public function finish(QueueItem $queueItem) {
    if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
      $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::COMPLETED);
    }

    $queueItem->setStatus(QueueItem::COMPLETED);
    $queueItem->setFinishTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
    $queueItem->setProgressBasePoints(10000);

    try {
      $this->save(
        $queueItem,
        ['status' => QueueItem::IN_PROGRESS, 'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp()]
      );
    }
    catch (QueueItemSaveException $exception) {
      throw new QueueStorageUnavailableException('Unable to finish task. Queue storage failed to save item.', 0, $exception);
    }
  }

  /**
   * Returns queue item back to queue and sets updates last execution progress to current progress value.
   *
   * @param QueueItem $queueItem
   *   Queue item to requeue.
   *
   * @throws QueueStorageUnavailableException
   */
  public function requeue(QueueItem $queueItem) {
    if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
      $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::QUEUED);
    }

    $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();

    $queueItem->setStatus(QueueItem::QUEUED);
    $queueItem->setStartTimestamp(NULL);
    $queueItem->setLastExecutionProgressBasePoints($queueItem->getProgressBasePoints());

    try {
      $this->save(
        $queueItem,
        [
          'status' => QueueItem::IN_PROGRESS,
          'lastExecutionProgress' => $lastExecutionProgress,
          'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
        ]
      );
    }
    catch (QueueItemSaveException $exception) {
      throw new QueueStorageUnavailableException('Unable to requeue task. Queue storage failed to save item.', 0, $exception);
    }
  }

  /**
   * Returns queue item back to queue and increments retries count. When max retries count is reached puts item in failed status.
   *
   * @param QueueItem $queueItem
   *   Queue item to fail.
   * @param string $failureDescription
   *   Verbal description of failure.
   *
   * @throws \BadMethodCallException Queue item must be in "in_progress" status for fail method
   * @throws QueueStorageUnavailableException
   */
  public function fail(QueueItem $queueItem, $failureDescription) {
    if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
      $this->throwIllegalTransitionException($queueItem->getStatus(), QueueItem::FAILED);
    }

    $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();

    $queueItem->setRetries($queueItem->getRetries() + 1);
    $queueItem->setFailureDescription($failureDescription);

    if ($queueItem->getRetries() > $this->getMaxRetries()) {
      $queueItem->setStatus(QueueItem::FAILED);
      $queueItem->setFailTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());
    }
    else {
      $queueItem->setStatus(QueueItem::QUEUED);
      $queueItem->setStartTimestamp(NULL);
    }

    try {
      $this->save(
        $queueItem,
        [
          'status' => QueueItem::IN_PROGRESS,
          'lastExecutionProgress' => $lastExecutionProgress,
          'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
        ]
      );
    }
    catch (QueueItemSaveException $exception) {
      throw new QueueStorageUnavailableException('Unable to fail task. Queue storage failed to save item.', 0, $exception);
    }
  }

  /**
   *
   */
  public function updateProgress(QueueItem $queueItem, $progress) {
    if ($queueItem->getStatus() !== QueueItem::IN_PROGRESS) {
      throw new \BadMethodCallException('Progress reported for not started queue item.');
    }

    $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();
    $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();

    $queueItem->setProgressBasePoints($progress);
    $queueItem->setLastUpdateTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

    try {
      $this->save($queueItem, [
        'status' => QueueItem::IN_PROGRESS,
        'lastExecutionProgress' => $lastExecutionProgress,
        'lastUpdateTimestamp' => $lastUpdateTimestamp,
      ]);
    }
    catch (QueueItemSaveException $exception) {
      throw new QueueStorageUnavailableException('Unable to update task progress. Queue storage failed to save item.', 0, $exception);
    }
  }

  /**
   *
   */
  public function keepAlive(QueueItem $queueItem) {
    $lastExecutionProgress = $queueItem->getLastExecutionProgressBasePoints();
    $lastUpdateTimestamp = $queueItem->getLastUpdateTimestamp();
    $queueItem->setLastUpdateTimestamp($this->getTimeProvider()->getCurrentLocalTime()->getTimestamp());

    try {
      $this->save($queueItem, [
        'status' => QueueItem::IN_PROGRESS,
        'lastExecutionProgress' => $lastExecutionProgress,
        'lastUpdateTimestamp' => $lastUpdateTimestamp,
      ]);
    }
    catch (QueueItemSaveException $exception) {
      throw new QueueStorageUnavailableException('Unable to keep task alive. Queue storage failed to save item.', 0, $exception);
    }
  }

  /**
   * Finds queue item by id.
   *
   * @param int $id
   *   Id of a queue item to find.
   *
   * @return QueueItem|null Found queue item or null when queue item does not exist
   */
  public function find($id) {
    return $this->getStorage()->find($id);
  }

  /**
   * Finds latest queue item by type.
   *
   * @param string $type
   *   Type of a queue item to find.
   *
   * @param string $context
   *   Task scope restriction, default is global scope.
   *
   * @return QueueItem|null Found queue item or null when queue item does not exist
   */
  public function findLatestByType($type, $context = '') {
    return $this->getStorage()->findLatestByType($type, $context);
  }

  /**
   * Finds queue items with status "in_progress".
   *
   * @return QueueItem[] Running queue items
   */
  public function findRunningItems() {
    return $this->getStorage()->findAll(['status' => QueueItem::IN_PROGRESS]);
  }

  /**
   * Finds list of earliest queued queue items per queue. Only queues that doesn't have running tasks are taken in consideration.
   *
   * @param int $limit
   *   Result set limit. By default max 10 earliest queue items will be returned.
   *
   * @return \CleverReach\Infrastructure\TaskExecution\QueueItem[] Found queue item list
   */
  public function findOldestQueuedItems($limit = 10) {
    return $this->getStorage()->findOldestQueuedItems($limit);
  }

  /**
   * Creates or updates given queue item using storage service. If queue item id is not set, new queue item will be created
   * otherwise update will be performed.
   *
   * @param QueueItem $queueItem
   *   Item to save.
   *
   * @param array $additionalWhere
   *   List of key/value pairs to set in where clause when saving queue item.
   *
   * @return int Id of saved queue item
   *
   * @throws QueueItemSaveException if save fails
   */
  private function save(QueueItem $queueItem, array $additionalWhere = []) {
    $id = $this->getStorage()->save($queueItem, $additionalWhere);
    $queueItem->setId($id);

    return $id;
  }

  /**
   *
   */
  private function getStorage() {
    if (empty($this->storage)) {
      $this->storage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
    }

    return $this->storage;
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
  private function getTaskRunnerWakeup() {
    if (empty($this->taskRunnerWakeup)) {
      $this->taskRunnerWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
    }

    return $this->taskRunnerWakeup;
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

  /**
   *
   */
  private function throwIllegalTransitionException($fromStatus, $toStatus) {
    throw new \BadMethodCallException(sprintf(
        'Illegal queue item state transition from "%s" to "%s"',
        $fromStatus,
        $toStatus
    ));
  }

  /**
   *
   */
  private function getMaxRetries() {
    $configurationValue = $this->getConfigService()->getMaxTaskExecutionRetries();
    return !is_null($configurationValue) ? $configurationValue : self::MAX_RETRIES;
  }

}
