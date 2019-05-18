<?php

namespace Drupal\purge_queues\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Queue\ProxyItemInterface;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\QueueInterface compliant database backed queue.
 *
 * @PurgeQueue(
 *   id = "database_unique",
 *   label = @Translation("Database unique"),
 *   description = @Translation("A scalable database backed queue that avoid duplicate items."),
 * )
 */
class DatabaseUniqueQueue extends AltDatabaseQueue {

  /**
   * Find the queue for a record representing $data.
   *
   * @return id of the record, or FALSE if not found.
   */
  protected function findItem($data) {
    $query = $this->connection->select(static::TABLE_NAME, 't')
      ->fields('t', ['item_id'])
      ->condition('type', $data[ProxyItemInterface::DATA_INDEX_TYPE])
      ->condition('expression', $data[ProxyItemInterface::DATA_INDEX_EXPRESSION])
      ->range(0, 1);
    $result = $query->execute()->fetchCol();

    return count($result) ? $result[0] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    if ($id = $this->findItem($data)) {
      return $id;
    }
    return parent::createItem($data);
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $_items = [];
    $_item_ids = [];

    // Find items already in the queue.
    // Store the queued item id in $_item_ids, indexed by $items key
    // Store the pending items in $_items, indexed by $items key
    foreach ($items as $id => $data) {
      if ($item_id = $this->findItem($data)) {
        $_item_ids[$id] = $item_id;
        continue;
      }
      $_items[$id] = $data;
    }

    $item_ids = parent::createItemMultiple($_items);

    // Merge the added items ids with the already existent ones.
    // $_items keys has the original position for each corresponding item id.
    $i=0;
    foreach ($_items as $id => $item) {
      $_item_ids[$id] = $item_ids[$i];
      $i++;
    }

    ksort($_item_ids);

    return $_item_ids;
  }
}
