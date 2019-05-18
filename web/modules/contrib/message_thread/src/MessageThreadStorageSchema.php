<?php

namespace Drupal\message_thread;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the term schema handler.
 */
class MessageThreadStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset = FALSE);

    $schema['message_thread_index'] = [
      'description' => 'Maintains denormalized information about thread/message relationships.',
      'fields' => [
        'mid' => [
          'description' => 'The {message}.mid this record tracks.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'thread_id' => [
          'description' => 'The thread ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'created' => [
          'description' => 'The Unix timestamp when the message was created.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
      'primary key' => ['mid', 'thread_id'],
      'indexes' => [
        'thread_message' => ['thread_id', 'created'],
      ],
      'foreign keys' => [
        'tracked_message' => [
          'table' => 'message',
          'columns' => ['mid' => 'mid'],
        ],
        'term' => [
          'table' => 'message_field_data',
          'columns' => ['mid' => 'mid'],
        ],
      ],
    ];

    return $schema;
  }

}
