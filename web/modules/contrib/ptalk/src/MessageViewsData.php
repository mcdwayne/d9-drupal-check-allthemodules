<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\views\EntityViewsData;

/**
 * Provides views data for the ptalk_message entity type.
 */
class MessageViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['ptalk_message']['table']['base']['help'] = $this->t('Private messages.');

    $data['ptalk_message']['created']['title'] = $this->t('Created');
    $data['ptalk_message']['created']['help'] = $this->t('Date and time when the message was sent.');

    $data['ptalk_message']['created_fulldata'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['ptalk_message']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['ptalk_message']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    $data['ptalk_message']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    $data['ptalk_message']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    $data['ptalk_message']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    $data['ptalk_message']['changed']['title'] = $this->t('Updated date');
    $data['ptalk_message']['changed']['help'] = $this->t('Date and time when the message was last updated.');

    $data['ptalk_message']['changed_fulldata'] = [
      'title' => $this->t('Changed date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['ptalk_message']['changed_year_month'] = [
      'title' => $this->t('Changed year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['ptalk_message']['changed_year'] = [
      'title' => $this->t('Changed year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['ptalk_message']['changed_month'] = [
      'title' => $this->t('Changed month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['ptalk_message']['changed_day'] = [
      'title' => $this->t('Changed day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['ptalk_message']['changed_week'] = [
      'title' => $this->t('Changed week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['ptalk_message']['tid'] = [
      'title' => t('Private conversation'),
      'help' => t('The private conversation to which message bolongs.'),
      'relationship' => [
        'title' => t('Conversation'),
        'base' => 'ptalk_thread',
        'base field' => 'tid',
        'relationship_field' => 'tid',
        'label' => t('Conversation'),
        'id' => 'standard',
        'label' => t('Private conversation'),
      ],
    ];

    // The base group of this table.
    $data['ptalk_message']['table']['group']  = $this->t('Message');

    // The base group of this table.
    $data['ptalk_message_index']['table']['group']  = $this->t('Message index');

    $data['ptalk_message_index']['table']['join']['ptalk_message'] = [
      'left_field' => 'mid',
      'field' => 'mid',
    ];

    $data['ptalk_message_index']['tid'] = [
      'title' => $this->t('Thread ID'),
      'help' => $this->t('Thread ID of the message.'),
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

    $data['ptalk_message_index']['recipient'] = [
      'title' => $this->t('Recipient'),
      'help' => $this->t('Recipient of the message.'),
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

    $data['ptalk_message_index']['status'] = [
      'title' => $this->t('Message status'),
      'help' => $this->t('Status of the message (read or unread).'),
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

    $data['ptalk_message_index']['deleted'] = [
      'title' => $this->t('Delete time'),
      'help' => $this->t('Time when the message was deleted.'),
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

    $data['ptalk_message_index']['type'] = [
      'title' => $this->t('Recipient type'),
      'help' => $this->t('Type of the recipient.'),
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

    return $data;
  }

}
