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

    return $data;
  }

}
