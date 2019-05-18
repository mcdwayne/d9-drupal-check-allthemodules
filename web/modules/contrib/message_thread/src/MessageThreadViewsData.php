<?php

namespace Drupal\message_thread;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the views data for the message entity type.
 */
class MessageThreadViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    // We establish Views handlers for message_thread_index.
    $data['message_thread_index']['table']['group'] = $this->t('Message Threads');
    $data['message_thread_index']['table']['join'] = [
      'message_thread_field_data' => [
        // Links directly to message thread via thread_id.
        'left_field' => 'thread_id',
        'field' => 'thread_id',
      ],
      'message_field_data' => [
        // Links directly to message via mid.
        'left_field' => 'mid',
        'field' => 'mid',
      ],
    ];

    $data['message_thread_index']['thread_id'] = [
      'title' => $this->t('Thread belonging to message'),
      'help' => $this->t('Thread belonging to a message.'),
      'argument' => [
        'id' => 'thread_id',
        'numeric' => TRUE,
      ],
      'relationship' => [
        'id' => 'standard',
        'base' => 'message_thread_field_data',
        'base field' => 'thread_id',
        'label' => $this->t('Message Thread to Thread'),
        'description' => $this->t('Link the message thread index to the thread.'),
      ],
    ];

    $data['message_thread_index']['mid'] = [
      'title' => $this->t('Messages belonging to Thread'),
      'help' => $this->t('Relate all content belonging to a thread.'),
      'argument' => [
        'id' => 'mid',
        'numeric' => TRUE,
      ],
      'relationship' => [
        'id' => 'standard',
        'base' => 'message_field_data',
        'base field' => 'mid',
        'label' => $this->t('Messages belonging to thread'),
        'description' => $this->t('Link the message thread index to the message.'),
      ],
    ];

    // Define the base group of this table.
    // Fields that don't have a group defined.
    // will go into this field by default.
    $data['message_thread_statistics']['table']['group'] = $this->t('Message Statistics');

    $data['message_thread_statistics']['table']['join'] = [
      'message_thread_field_data' => [
        // Links directly to message thread via thread_id.
        'left_field' => 'thread_id',
        'field' => 'entity_id',
      ],
    ];

    $data['message_thread_statistics']['last_message_timestamp'] = [
      'title' => $this->t('Last message time'),
      'help' => $this->t('Date and time of when the last message was posted.'),
      'field' => [
        'id' => 'message_last_timestamp',
      ],
      'sort' => [
        'id' => 'date',
      ],
      'filter' => [
        'id' => 'date',
      ],
    ];

    $data['message_thread_statistics']['last_message_name'] = [
      'title' => $this->t("Last message author"),
      'help' => $this->t('The name of the author of the last posted message.'),
      'field' => [
        'id' => 'message_last_name',
        'no group by' => TRUE,
      ],
      'sort' => [
        'id' => 'message_last_name',
        'no group by' => TRUE,
      ],
    ];

    $data['message_thread_statistics']['message_count'] = [
      'title' => $this->t('Message count'),
      'help' => $this->t('The number of messages an entity has.'),
      'field' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'standard',
      ],
    ];

    $data['message_thread_statistics']['last_updated'] = [
      'title' => $this->t('Message date'),
      'help' => $this->t('The most recent of last message posted.'),
      'field' => [
        'id' => 'message_last_updated',
        'no group by' => TRUE,
      ],
      'sort' => [
        'id' => 'message_last_updated',
        'no group by' => TRUE,
      ],
      'filter' => [
        'id' => 'message_last_updated',
      ],
    ];

    $data['message_thread_statistics']['mid'] = [
      'title' => $this->t('Last message CID'),
      'help' => $this->t('Display the last message of an entity'),
      'relationship' => [
        'title' => $this->t('Last message'),
        'help' => $this->t('The last message of an entity.'),
        'group' => $this->t('Message'),
        'base' => 'message',
        'base field' => 'mid',
        'id' => 'standard',
        'label' => $this->t('Last Message'),
      ],
    ];

    $data['message_thread_statistics']['last_message_uid'] = [
      'title' => $this->t('Last message uid'),
      'help' => $this->t('The User ID of the author of the last message of an entity.'),
      'relationship' => [
        'title' => $this->t('Last message author'),
        'base' => 'users',
        'base field' => 'uid',
        'id' => 'standard',
        'label' => $this->t('Last message author'),
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'field' => [
        'id' => 'numeric',
      ],
    ];

    $data['message_thread_statistics']['entity_type'] = [
      'title' => $this->t('Entity type'),
      'help' => $this->t('The entity type to which the message is a reply to.'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    return $data;
  }

}
