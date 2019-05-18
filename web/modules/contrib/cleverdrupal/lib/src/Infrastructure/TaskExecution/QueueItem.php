<?php

namespace CleverReach\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Infrastructure\Utility\TimeProvider;

/**
 *
 */
class QueueItem {
  const CREATED = 'created';
  const QUEUED = 'queued';
  const IN_PROGRESS = 'in_progress';
  const COMPLETED = 'completed';
  const FAILED = 'failed';

  /**
   * @var int*/
  private $id;

  /**
   * @var string*/
  private $status;

  /**
   * @var Task*/
  private $task;

  /**
   * @var string*/
  private $context;

  /**
   * @var string*/
  private $serializedTask;

  /**
   * @var string*/
  private $queueName;

  /**
   * @var int
   */
  private $lastExecutionProgressBasePoints;

  /**
   * @var int
   */
  private $progressBasePoints;

  /**
   * @var int*/
  private $retries;

  /**
   * @var string*/
  private $failureDescription;

  /**
   * @var \DateTime*/
  private $createTime;

  /**
   * @var \DateTime*/
  private $startTime;

  /**
   * @var \DateTime*/
  private $finishTime;

  /**
   * @var \DateTime*/
  private $failTime;

  /**
   * @var \DateTime*/
  private $earliestStartTime;

  /**
   * @var \DateTime*/
  private $queueTime;

  /**
   * @var \DateTime*/
  private $lastUpdateTime;

  /**
   * @var \CleverReach\Infrastructure\Utility\TimeProvider*/
  private $timeProvider;

  /**
   *
   */
  public function __construct(Task $task = NULL, $context = '') {
    /** @var \CleverReach\Infrastructure\Utility\TimeProvider $timeProvider */
    $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

    $this->task = $task;
    $this->context = $context;
    $this->status = self::CREATED;
    $this->lastExecutionProgressBasePoints = 0;
    $this->progressBasePoints = 0;
    $this->retries = 0;
    $this->failureDescription = '';
    $this->createTime = $this->timeProvider->getCurrentLocalTime();

    $this->attachTaskEventHandlers();
  }

  /**
   * Gets queue item id.
   *
   * @return int Queue item id
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets queue item id.
   *
   * @param int $id
   *   Queue item id.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Gets queue item status.
   *
   * @return string Queue item status
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * Sets queue item status.
   *
   * @param string $status
   *   Queue item status. One of QueueItem::CREATED, QueueItem::QUEUED, QueueItem::IN_PROGRESS,
   *   QueueItem::COMPLETED or QueueItem::FAILED.
   */
  public function setStatus($status) {
    if (!in_array($status, [self::CREATED, self::QUEUED, self::IN_PROGRESS, self::COMPLETED, self::FAILED])) {
      throw new \InvalidArgumentException(sprintf(
        'Invalid QueueItem status: "%s". Status must be one of "%s", "%s", "%s", "%s" or "%s" values.',
        $status,
        self::CREATED,
        self::QUEUED,
        self::IN_PROGRESS,
        self::COMPLETED,
        self::FAILED
      ));
    }

    $this->status = $status;
  }

  /**
   * Gets queue item queue name.
   *
   * @return string Queue item queue name
   */
  public function getQueueName() {
    return $this->queueName;
  }

  /**
   * Sets queue item queue name.
   *
   * @param string $queueName
   *   Queue item queue name.
   */
  public function setQueueName($queueName) {
    $this->queueName = $queueName;
  }

  /**
   * Gets queue item last execution progress in base points as value between 0 and 10000. One base point is equal
   * to 0.01%. For example 23.58% is equal to 2358 base points.
   *
   * @return int Last execution progress expressed in base points
   */
  public function getLastExecutionProgressBasePoints() {
    return $this->lastExecutionProgressBasePoints;
  }

  /**
   * Sets queue item last execution progress in base points, as value between 0 and 10000. One base point is equal
   * to 0.01%. For example 23.58% is equal to 2358 base points.
   *
   * @param int $lastExecutionProgressBasePoints
   *   Queue item last execution progress in base points.
   */
  public function setLastExecutionProgressBasePoints($lastExecutionProgressBasePoints) {
    if (!is_int($lastExecutionProgressBasePoints) || $lastExecutionProgressBasePoints < 0 || 10000 < $lastExecutionProgressBasePoints) {
      throw new \InvalidArgumentException('Last execution progress percentage must be value between 0 and 100.');
    }

    $this->lastExecutionProgressBasePoints = $lastExecutionProgressBasePoints;
  }

  /**
   * Gets progress in percentage rounded to 2 decimal value.
   *
   * @return float QueueItem progress in percentage rounded to 2 decimal value
   */
  public function getProgressFormatted() {
    return round($this->progressBasePoints / 100, 2);
  }

  /**
   * Gets queue item progress in base points as value between 0 and 10000. One base point is equal
   * to 0.01%. For example 23.58% is equal to 2358 base points.
   *
   * @return int Queue item progress percentage in base points
   */
  public function getProgressBasePoints() {
    return $this->progressBasePoints;
  }

  /**
   * Sets queue item progress in base points, as value between 0 and 10000. One base point is equal
   * to 0.01%. For example 23.58% is equal to 2358 base points.
   *
   * @param int $progressBasePoints
   *   Queue item progress in base points.
   */
  public function setProgressBasePoints($progressBasePoints) {
    if (!is_int($progressBasePoints) || $progressBasePoints < 0 || 10000 < $progressBasePoints) {
      throw new \InvalidArgumentException('Progress percentage must be value between 0 and 100.');
    }

    $this->progressBasePoints = $progressBasePoints;
  }

  /**
   * Gets queue item retries count.
   *
   * @return int Queue item retries count
   */
  public function getRetries() {
    return $this->retries;
  }

  /**
   * Sets queue item retries count.
   *
   * @param int $retries
   *   Queue item retries count.
   */
  public function setRetries($retries) {
    $this->retries = $retries;
  }

  /**
   * Gets queue item task type.
   *
   * @return string Queue item task type
   *
   * @throws QueueItemDeserializationException
   */
  public function getTaskType() {
    $task = $this->getTask();
    return !empty($task) ? $task->getType() : '';
  }

  /**
   * Gets queue item associated task or null if not set.
   *
   * @return Task|null Queue item associated task
   *
   * @throws QueueItemDeserializationException
   */
  public function getTask() {
    if (empty($this->task)) {
      $this->task = @unserialize($this->serializedTask);
      if (empty($this->task)) {
        throw new QueueItemDeserializationException(json_encode([
          'Message' => 'Unable to deserialize queue item task',
          'SerializedTask' => $this->serializedTask,
          'QueueItemId' => $this->getId(),
        ]));
      }

      $this->attachTaskEventHandlers();
    }

    return !empty($this->task) ? $this->task : NULL;
  }

  /**
   * Gets serialized queue item task.
   *
   * @return string Serialized representation of queue item task
   */
  public function getSerializedTask() {
    if (empty($this->task)) {
      return $this->serializedTask;
    }

    return serialize($this->task);
  }

  /**
   * Sets serialized task representation.
   *
   * @param string $serializedTask
   */
  public function setSerializedTask($serializedTask) {
    $this->serializedTask = $serializedTask;
    $this->task = NULL;
  }

  /**
   * Gets task execution context.
   *
   * @return string Context in which task will be executed
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * Sets task execution context. Context in which task will be executed.
   *
   * @param string $context
   *   Execution context.
   */
  public function setContext($context) {
    $this->context = $context;
  }

  /**
   * Gets queue item failure description.
   *
   * @return string Queue item failure description
   */
  public function getFailureDescription() {
    return $this->failureDescription;
  }

  /**
   * Sets queue item failure description.
   *
   * @param string $failureDescription
   *   Queue item failure description.
   */
  public function setFailureDescription($failureDescription) {
    $this->failureDescription = $failureDescription;
  }

  /**
   * Gets queue item created timestamp.
   *
   * @return int|null Queue item created timestamp
   */
  public function getCreateTimestamp() {
    return $this->getTimestamp($this->createTime);
  }

  /**
   * Sets queue item created timestamp.
   *
   * @param int $createTimestamp
   *   Sets queue item created timestamp.
   */
  public function setCreateTimestamp($createTimestamp) {
    $this->createTime = !empty($createTimestamp) ? new \DateTime("@{$createTimestamp}") : NULL;
  }

  /**
   * Gets queue item start timestamp or null if task is not started.
   *
   * @return int|null Queue item start timestamp
   */
  public function getStartTimestamp() {
    return $this->getTimestamp($this->startTime);
  }

  /**
   * Sets queue item start timestamp.
   *
   * @param int $startTimestamp
   *   Queue item start timestamp.
   */
  public function setStartTimestamp($startTimestamp) {
    $this->startTime = !empty($startTimestamp) ? new \DateTime("@{$startTimestamp}") : NULL;
  }

  /**
   * Gets queue item finish timestamp or null if task is not finished.
   *
   * @return int|null Queue item finish timestamp
   */
  public function getFinishTimestamp() {
    return $this->getTimestamp($this->finishTime);
  }

  /**
   * Sets queue item finish timestamp.
   *
   * @param int $finishTimestamp
   *   Queue item finish timestamp.
   */
  public function setFinishTimestamp($finishTimestamp) {
    $this->finishTime = !empty($finishTimestamp) ? new \DateTime("@{$finishTimestamp}") : NULL;
  }

  /**
   * Gets queue item fail timestamp or null if task is not failed.
   *
   * @return int|null Queue item fail timestamp
   */
  public function getFailTimestamp() {
    return $this->getTimestamp($this->failTime);
  }

  /**
   * Sets queue item fail timestamp.
   *
   * @param int $failTimestamp
   *   Queue item fail timestamp.
   */
  public function setFailTimestamp($failTimestamp) {
    $this->failTime = !empty($failTimestamp) ? new \DateTime("@{$failTimestamp}") : NULL;
  }

  /**
   * Gets queue item earliest start timestamp or null if not set.
   *
   * @return int|null Queue item earliest start timestamp
   */
  public function getEarliestStartTimestamp() {
    return $this->getTimestamp($this->earliestStartTime);
  }

  /**
   * Sets queue item earliest start timestamp.
   *
   * @param int $earliestStartTimestamp
   *   Queue item earliest start timestamp.
   */
  public function setEarliestStartTimestamp($earliestStartTimestamp) {
    $this->earliestStartTime = !empty($earliestStartTimestamp) ? new \DateTime("@{$earliestStartTimestamp}") : NULL;
  }

  /**
   * Gets queue item queue timestamp or null if task is not queued.
   *
   * @return int|null Queue item queue timestamp
   */
  public function getQueueTimestamp() {
    return $this->getTimestamp($this->queueTime);
  }

  /**
   * Gets queue item queue timestamp.
   *
   * @param int $queueTimestamp
   *   Queue item queue timestamp.
   */
  public function setQueueTimestamp($queueTimestamp) {
    $this->queueTime = !empty($queueTimestamp) ? new \DateTime("@{$queueTimestamp}") : NULL;
  }

  /**
   * Gets queue item last updated timestamp or null if task was never updated.
   *
   * @return int|null Queue item last updated timestamp
   */
  public function getLastUpdateTimestamp() {
    return $this->getTimestamp($this->lastUpdateTime);
  }

  /**
   * Reconfigures underlying task.
   *
   * @throws QueueItemDeserializationException
   */
  public function reconfigureTask() {
    $task = $this->getTask();

    if ($task !== NULL && $task->canBeReconfigured()) {
      $task->reconfigure();
      $this->setRetries(0);
      Logger::logDebug('Task ' . $this->getTaskType() . ' reconfigured.');
    }
  }

  /**
   * Sets queue item last updated timestamp.
   *
   * @param int $lastUpdateTimestamp
   *   Queue item last updated timestamp.
   */
  public function setLastUpdateTimestamp($lastUpdateTimestamp) {
    $this->lastUpdateTime = !empty($lastUpdateTimestamp) ? new \DateTime("@{$lastUpdateTimestamp}") : NULL;
  }

  /**
   *
   */
  private function attachTaskEventHandlers() {
    if (empty($this->task)) {
      return;
    }

    $self = $this;
    $this->task->when(ProgressedTaskEvent::CLASS_NAME, function (ProgressedTaskEvent $event) use ($self) {
      $queue = new Queue();
      $queue->updateProgress($self, $event->getProgressBasePoints());
    });

    $this->task->when(AliveAnnouncedTaskEvent::CLASS_NAME, function () use ($self) {
      $queue = new Queue();
      $queue->keepAlive($self);
    });
  }

  /**
   * Gets timestamp of time or null if time is not defined.
   *
   * @param \DateTime|null $time
   *
   * @return int|null
   */
  private function getTimestamp(\DateTime $time = NULL) {
    return !empty($time) ? $time->getTimestamp() : NULL;
  }

}
