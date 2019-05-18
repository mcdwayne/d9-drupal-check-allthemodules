<?php

namespace Drupal\purge_queues\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Queue\DatabaseQueue;
use Drupal\purge\Plugin\Purge\Queue\ProxyItemInterface;

/**
 * A \Drupal\purge\Plugin\Purge\Queue\QueueInterface compliant database backed queue.
 *
 * @PurgeQueue(
 *   id = "database_alt",
 *   label = @Translation("Database (extended)"),
 *   description = @Translation("Extension of the database queue, with additional columns to store the invalidation type and expression."),
 * )
 */
class AltDatabaseQueue extends DatabaseQueue {

  /**
   * The active Drupal database connection object.
   */
  const TABLE_NAME = 'purge_queue_alt';

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $query = $this->connection->insert(static::TABLE_NAME)
      ->fields(array(
        'type' => $data[ProxyItemInterface::DATA_INDEX_TYPE],
        'expression' => $data[ProxyItemInterface::DATA_INDEX_EXPRESSION],
        'data' => serialize($data),
        'created' => time(),
      ));
    if ($id = $query->execute()) {
      return (int) $id;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createItemMultiple(array $items) {
    $item_ids = $records = [];

    // Build a array with all exactly records as they should turn into rows.
    $time = time();
    foreach ($items as $data) {
      $records[] = [
        'type' => $data[ProxyItemInterface::DATA_INDEX_TYPE],
        'expression' => $data[ProxyItemInterface::DATA_INDEX_EXPRESSION],
        'data' => serialize($data),
        'created' => $time,
      ];
    }

    // Insert all of them using just one multi-row query.
    $query = $this->connection->insert(static::TABLE_NAME, [])->fields(['type', 'expression', 'data', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }

    // Execute the query and finish the call.
    if ($id = $query->execute()) {
      $id = (int) $id;

      // A multiple row-insert doesn't give back all the individual IDs, so
      // calculate them back by applying subtraction.
      for ($i = 1; $i <= count($records); $i++) {
        $item_ids[] = $id;
        $id++;
      }
      return $item_ids;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function schemaDefinition() {
    return [
      'description' => 'Queue items for the purge database_unpacked queue plugin.',
      'fields' => [
        'item_id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'Primary Key: Unique item ID.',
        ],
        'type' => [
          'type' => 'varchar_ascii',
          'length' => 32,
          'not null' => TRUE,
          'description' => 'The invalidation type.',
        ],
        'expression' => [
          'type' => 'varchar',
          'length' => 2048,
          'not null' => TRUE,
          'description' => 'The invalidation expression.',
        ],
        'data' => [
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
          'serialize' => TRUE,
          'description' => 'The arbitrary data for the item.',
        ],
        'expire' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Timestamp when the claim lease expires on the item.',
        ],
        'created' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Timestamp when the item was created.',
        ],
      ],
      'primary key' => ['item_id'],
      'indexes' => [
        'type_expression' => ['type', 'expression'],
        'created' => ['created'],
        'expire' => ['expire'],
      ],
    ];
  }

}
