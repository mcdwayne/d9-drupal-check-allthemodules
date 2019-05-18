<?php

namespace Drupal\cleverreach\Component\Infrastructure;

use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use Drupal\cleverreach\Component\Repository\QueueRepository;

/**
 * Task queue storage service implementation.
 *
 * @see \CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage
 */
class TaskQueueStorageService implements TaskQueueStorage {
  /**
   * @var \Drupal\cleverreach\Component\Repository\QueueRepository
   */
  protected $taskQueueRepository;

  /**
   * TaskQueueStorageService constructor.
   */
  public function __construct() {
    $this->taskQueueRepository = new QueueRepository();
  }

  /**
   * @inheritdoc
   */
  public function save(QueueItem $queueItem, array $additionalWhere = []) {
    $itemId = NULL;
    try {
      $queueItemId = $queueItem->getId();
      if (NULL === $queueItemId || $queueItemId <= 0) {
        $itemId = $this->taskQueueRepository->insert($this->queueItemToArray($queueItem));
      }
      else {
        $this->updateQueueItem($queueItem, $additionalWhere);
        $itemId = $queueItemId;
      }
    }
    catch (\Exception $exception) {
      throw new QueueItemSaveException(
          'Failed to save queue item with id: ' . $itemId, 0, $exception
      );
    }

    return $itemId;
  }

  /**
   * @inheritdoc
   */
  public function find($id) {
    $queueItem = $this->queueItemFromArray(
        $this->taskQueueRepository->findById($id)
    );

    if ($queueItem === NULL) {
      Logger::logDebug(
        json_encode(
            ['Message' => "Failed to fetch queue item with id: $id. Queue item  does not exist."]
        )
      );
    }

    return $queueItem;
  }

  /**
   * @inheritdoc
   */
  public function findLatestByType($type, $context = '') {
    $queueItem = $this->queueItemFromArray(
        $this->taskQueueRepository->findLatest($type)
    );

    if ($queueItem === NULL) {
      Logger::logDebug(
        json_encode(['Message' => "Failed to fetch queue item with type: $type. Queue item  does not exist."])
      );
    }

    return $queueItem;
  }

  /**
   * @inheritdoc
   */
  public function findOldestQueuedItems($limit = 10) {
    return $this->queueItemsFromArray(
        $this->taskQueueRepository->findOldestQueuedItems((int) $limit)
    );
  }

  /**
   * @inheritdoc
   */
  public function findAll(array $filterBy = [], array $sortBy = [], $start = 0, $limit = 10) {
    return $this->queueItemsFromArray(
        $this->taskQueueRepository->findAll($filterBy, $sortBy, (int) $start, (int) $limit)
    );
  }

  /**
   * Updates database record with data from provided $queueItem.
   *
   * @param \CleverReach\Infrastructure\TaskExecution\QueueItem $queueItem
   * @param array $conditions
   *
   * @throws QueueItemSaveException
   */
  private function updateQueueItem($queueItem, array $conditions = []) {
    // CORE can give additional conditions to preserve updating item that is possibly locked or already updated
    // this is done because of update lock.
    $conditions = array_merge($conditions, ['id' => $queueItem->getId()]);

    $item = $this->taskQueueRepository->findOne($conditions);
    if (empty($item)) {
      $message = 'Failed to save queue item, update condition(s) not met.';
      Logger::logDebug(json_encode(['Message' => $message, 'WhereCondition' => json_encode($conditions)]));
      throw new QueueItemSaveException($message);
    }

    $item = array_merge($item, $this->queueItemToArray($queueItem));
    $this->taskQueueRepository->update($item, $conditions);
  }

  /**
   * Serializes instance of QueueItem to array of values.
   *
   * @param \CleverReach\Infrastructure\TaskExecution\QueueItem $queueItem
   *
   * @return array
   *   Array representation of queue item.
   */
  private function queueItemToArray(QueueItem $queueItem) {
    return [
      'id' => $queueItem->getId(),
      'type' => $queueItem->getTaskType(),
      'status' => $queueItem->getStatus(),
      'queue_name' => $queueItem->getQueueName(),
      'progress' => $queueItem->getProgressFormatted(),
      'last_execution_progress' => $queueItem->getLastExecutionProgressBasePoints(),
      'retries' => $queueItem->getRetries(),
      'failure_description' => $queueItem->getFailureDescription(),
      'serialized_task' => $queueItem->getSerializedTask(),
      'create_timestamp' => $queueItem->getCreateTimestamp(),
      'queue_timestamp' => $queueItem->getQueueTimestamp(),
      'last_update_timestamp' => $queueItem->getLastUpdateTimestamp(),
      'start_timestamp' => $queueItem->getStartTimestamp(),
      'finish_timestamp' => $queueItem->getFinishTimestamp(),
      'fail_timestamp' => $queueItem->getFailTimestamp(),
    ];
  }

  /**
   * Transforms array of values to QueueItem instance.
   *
   * @param array|null $item
   *   Array representation of queue item.
   *
   * @return \CleverReach\Infrastructure\TaskExecution\QueueItem
   *   Object representation of queue item.
   */
  private function queueItemFromArray($item) {
    if (empty($item)) {
      return NULL;
    }

    $queueItem = new QueueItem();
    $queueItem->setId((int) $item['id']);
    $queueItem->setStatus($item['status']);
    $queueItem->setQueueName($item['queue_name']);
    $queueItem->setProgressBasePoints((int) $item['progress']);
    $queueItem->setLastExecutionProgressBasePoints((int) $item['last_execution_progress']);
    $queueItem->setRetries((int) $item['retries']);
    $queueItem->setFailureDescription($item['failure_description']);
    $queueItem->setSerializedTask($item['serialized_task']);
    $queueItem->setCreateTimestamp((int) $item['create_timestamp']);
    $queueItem->setQueueTimestamp((int) $item['queue_timestamp']);
    $queueItem->setLastUpdateTimestamp((int) $item['last_update_timestamp']);
    $queueItem->setStartTimestamp((int) $item['start_timestamp']);
    $queueItem->setFinishTimestamp((int) $item['finish_timestamp']);
    $queueItem->setFailTimestamp((int) $item['fail_timestamp']);

    return $queueItem;
  }

  /**
   * Transforms array of values to array of QueueItem instances.
   *
   * @param array|null $items
   *   List of items that need to be converted to list of QueueItem objects.
   *
   * @return \CleverReach\Infrastructure\TaskExecution\QueueItem[]
   */
  private function queueItemsFromArray($items) {
    $result = [];
    if (empty($items)) {
      return $result;
    }

    foreach ($items as $item) {
      $result[] = $this->queueItemFromArray($item);
    }

    return $result;
  }

}
