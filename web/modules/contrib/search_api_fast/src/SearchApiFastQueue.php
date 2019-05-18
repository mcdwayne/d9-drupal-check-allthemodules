<?php

namespace Drupal\search_api_fast;

//use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Queue\DatabaseQueue;

/**
 * @file
 * The mightyfine SearchApiFastSearchQueue class.
 *
 * Provides method to inject multiple items into queue at once.
 */

/**
 * @file
 * The mightyfine SearchApiFastQueue class.
 *
 * Provides method to inject multiple items into queue at once.
 */
class SearchApiFastQueue extends DatabaseQueue {

  /**
   * Create a number of items in one query.
   *
   * @param array $data
   *   Array containing data.
   *   Each item will become a separate queue item.
   *
   * @return bool
   *   Good or wrong ? Black or white? DUH...
   */
  public function createItems(array $data) {

    $query = $this->connection->insert(static::TABLE_NAME)
      ->fields(array('name', 'data', 'created'));

    $time = time();
    foreach ($data as $item) {
      $record = array(
        'name' => $this->name,
        'data' => serialize($item),
        'created' => $time,
      );

      $query->values($record);
    }
    return (bool) $query->execute();
  }

  /**
   * Claim queue items for certain amount of time.
   *
   * This cleams multiple items.
   * Note that this is not bullet-proof. The normal claimItem() method
   * only claims 1 item. This one says it's all ok if one of $limit items
   * is claimed.
   *
   * @param int $limit
   *   Max number of items.
   * @param int $lease_time
   *   Lease time.
   *
   * @return array|bool
   *   Claimed items, of FAILSE on failure.
   */
  public function claimItems($limit = 10, $lease_time = 30) {

    // Claim an item by updating its expire fields. If claim is not successful
    // another thread may have claimed the item in the meantime. Therefore loop
    // until an item is successfully claimed or we are reasonably sure there
    // are no unclaimed items left.
    while (TRUE) {
      $items = $this->connection->queryRange('SELECT item_id, data FROM {' . static::TABLE_NAME . '} q WHERE expire = 0 AND name = :name ORDER BY created, item_id ASC', 0, $limit, array(':name' => $this->name))->fetchAllAssoc('item_id');
      if ($items) {

        // Try to update the item. Only one thread can succeed in UPDATEing the
        // same row. We cannot rely on REQUEST_TIME because items might be
        // claimed by a single consumer which runs longer than 1 second. If we
        // continue to use REQUEST_TIME instead of the current time(), we steal
        // time from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = $this->connection->update(static::TABLE_NAME)
          ->fields(array(
            'expire' => time() + $lease_time,
          ))
          ->condition('item_id', array_keys($items), 'IN')
          ->condition('expire', 0);
        // If there are affected rows, this update succeeded.
        if ($update->execute()) {
          $data = array();
          foreach ($items as $item_id => $item) {
            $data[$item_id] = unserialize($item->data);
          }
          return $data;
        }
      }
      else {
        // No items currently available to claim.
        return FALSE;
      }
    }
    return FALSE;
  }

  /**
   * Delete multiple items from queue.
   *
   * @param array $items
   *   Array containing queue-item ID's.
   */
  public function deleteItems($items) {
    $this->connection->delete(static::TABLE_NAME)
      ->condition('item_id', $items, 'IN')
      ->execute();
  }

}
