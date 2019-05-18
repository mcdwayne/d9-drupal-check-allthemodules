<?php

namespace Drupal\queue_unique;

use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Queue\DatabaseQueue;

/**
 * Database queue implementation which only adds unique items.
 */
class UniqueDatabaseQueue extends DatabaseQueue {

  /**
   * The database table name.
   *
   * We need a separate table for unique queues as we use a different schema.
   */
  const TABLE_NAME = 'queue_unique';

  /**
   * {@inheritdoc}
   */
  public function doCreateItem($data) {
    try {
      $query = $this->connection->insert(static::TABLE_NAME)
        ->fields([
          'name' => $this->name,
          'data' => serialize($data),
          'created' => time(),
          // Generate a unique value for this data on this queue.
          'md5' => md5($this->name . serialize($data))
        ]);
      return $query->execute();
    }
    catch (IntegrityConstraintViolationException $e) {
      // Assume this is because we have violated the uniqueness constraint.
      // Return FALSE to indicate that no item has been placed on the queue as
      // specified by QueueInterface.
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function schemaDefinition() {
    return array_merge_recursive(
      parent::schemaDefinition(),
      // We cannot create a unique key on the data field because it is a blob.
      // Instead we merge an additional field which should contain a checksum
      // of the data and an unique key for this field into the original schema
      // definition. These are used to ensure uniqueness.
      [
        'fields' => [
          'md5' => [
            'type' => 'char',
            'length' => 32,
            'not null' => TRUE,
          ]
        ],
        'unique keys' => [
          'unique' => ['md5']
        ]
      ]
    );
  }

}
