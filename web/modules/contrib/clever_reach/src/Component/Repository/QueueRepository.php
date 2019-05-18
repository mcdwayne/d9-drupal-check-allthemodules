<?php

namespace Drupal\clever_reach\Component\Repository;

use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use Drupal;
use PDO;

/**
 * Queue repository class.
 */
class QueueRepository extends BaseRepository {
  const TABLE_NAME = 'cleverreach_queue';

  /**
   * Finds latest queue item by type.
   *
   * @param string $type
   *   Queue type.
   *
   * @return array|null
   *   Found queue item list
   */
  public function findLatest($type) {
    return $this->findOne(['type' => $type], ['queue_timestamp' => TaskQueueStorage::SORT_DESC]);
  }

  /**
   * Finds list of earliest queued queue items per queue.
   *
   * Following list of criteria for searching must be satisfied:
   *  - Queue must be without already running queue items.
   *  - For one queue only one (oldest queued) item should be returned.
   *
   * @param int $limit
   *   Result set limit.
   *   By default max 10 earliest queue items will be returned.
   *
   * @return array
   *   Found queue item list.
   */
  public function findOldestQueuedItems($limit = 10) {
    // Get running queues names.
    $runningQuery = Drupal::database()->select(self::TABLE_NAME);
    $runningQuery->fields(self::TABLE_NAME, ['queue_name'])->condition('status', QueueItem::IN_PROGRESS);

    if (!$execute = $runningQuery->execute()) {
      return [];
    }

    if (!empty($execute->fetchAll())) {
      return [];
    }

    // Get non-running queues names.
    $nonRunningQuery = Drupal::database()->select(self::TABLE_NAME);
    $nonRunningQuery->fields(self::TABLE_NAME)->condition('status', QueueItem::QUEUED);
    $nonRunningQuery->orderBy('queue_timestamp');
    $nonRunningQuery->range(0, $limit);

    if (!$execute = $nonRunningQuery->execute()) {
      return [];
    }

    return $execute->fetchAllAssoc(self::TABLE_PK, PDO::FETCH_ASSOC);
  }

}
