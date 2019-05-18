<?php

namespace Drupal\ptalk;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\views\EntityViewsData;

/**
 * Provides views data for the ptalk_thread entity type.
 */
class ThreadViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['ptalk_thread']['table']['base']['help'] = $this->t('Private conversations.');

    $data['ptalk_thread']['subject']['field']['default_formatter_settings'] = ['link_to_entity' => TRUE];

    $data['ptalk_thread']['subject']['field']['link_to_ptalk_thread default'] = TRUE;

    $data['ptalk_thread']['ptalk_thread_bulk_form'] = [
      'title' => $this->t('Thread operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple conversations.'),
      'field' => [
        'id' => 'ptalk_thread_bulk_form',
      ],
    ];

    $data['ptalk_thread']['created']['title'] = $this->t('Created');
    $data['ptalk_thread']['created']['help'] = $this->t('Date and time of when the conversation was created.');

    $data['ptalk_thread']['created_fulldata'] = [
      'title' => $this->t('Created date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_fulldate',
      ],
    ];

    $data['ptalk_thread']['created_year_month'] = [
      'title' => $this->t('Created year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year_month',
      ],
    ];

    $data['ptalk_thread']['created_year'] = [
      'title' => $this->t('Created year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_year',
      ],
    ];

    $data['ptalk_thread']['created_month'] = [
      'title' => $this->t('Created month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_month',
      ],
    ];

    $data['ptalk_thread']['created_day'] = [
      'title' => $this->t('Created day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_day',
      ],
    ];

    $data['ptalk_thread']['created_week'] = [
      'title' => $this->t('Created week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'created',
        'id' => 'date_week',
      ],
    ];

    $data['ptalk_thread']['changed']['title'] = $this->t('Updated date');
    $data['ptalk_thread']['changed']['help'] = $this->t('Date and time of when the conversation was last updated.');

    $data['ptalk_thread']['changed_fulldata'] = [
      'title' => $this->t('Changed date'),
      'help' => $this->t('Date in the form of CCYYMMDD.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_fulldate',
      ],
    ];

    $data['ptalk_thread']['changed_year_month'] = [
      'title' => $this->t('Changed year + month'),
      'help' => $this->t('Date in the form of YYYYMM.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year_month',
      ],
    ];

    $data['ptalk_thread']['changed_year'] = [
      'title' => $this->t('Changed year'),
      'help' => $this->t('Date in the form of YYYY.'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_year',
      ],
    ];

    $data['ptalk_thread']['changed_month'] = [
      'title' => $this->t('Changed month'),
      'help' => $this->t('Date in the form of MM (01 - 12).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_month',
      ],
    ];

    $data['ptalk_thread']['changed_day'] = [
      'title' => $this->t('Changed day'),
      'help' => $this->t('Date in the form of DD (01 - 31).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_day',
      ],
    ];

    $data['ptalk_thread']['changed_week'] = [
      'title' => $this->t('Changed week'),
      'help' => $this->t('Date in the form of WW (01 - 53).'),
      'argument' => [
        'field' => 'changed',
        'id' => 'date_week',
      ],
    ];

    $data['ptalk_thread']['table']['group']  = $this->t('Thread');

    $data['ptalk_thread']['uid']['help'] = $this->t('The user authoring the conversation. If you need more fields than the uid add the content: author relationship');
    $data['ptalk_thread']['uid']['filter']['id'] = 'user_name';
    $data['ptalk_thread']['uid']['relationship']['title'] = $this->t('Conversation author');
    $data['ptalk_thread']['uid']['relationship']['help'] = $this->t('Relate conversation to the user who created it.');
    $data['ptalk_thread']['uid']['relationship']['label'] = $this->t('Author');

    $data['ptalk_thread']['participants']['field'] = [
      'title' => $this->t('Participants'),
      'help' => $this->t('Display participants of the thread.'),
      'id' => 'ptalk_thread_participants',
    ];

    // The base group of this table.
    $data['ptalk_thread_index']['table']['group']  = $this->t('Thread Index');

    $data['ptalk_thread_index']['table']['join']['ptalk_thread'] = [
      'left_field' => 'tid',
      'field' => 'tid',
    ];

    $data['ptalk_thread_index']['message_count'] = [
      'title' => $this->t('Message count'),
      'help' => $this->t('The number of messages a thread has.'),
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

    $data['ptalk_thread_index']['new_count'] = [
      'title' => $this->t('New count'),
      'help' => $this->t('The number of new messages for the current participant.'),
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

    $data['ptalk_thread_index']['status'] = [
      'title' => $this->t('Thread status'),
      'help' => $this->t('Status of the thread.'),
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

    $data['ptalk_thread_index']['deleted'] = [
      'title' => $this->t('Delete time'),
      'help' => $this->t('Time when the thread was deleted.'),
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

    $data['ptalk_thread_index']['participant'] = [
      'title' => $this->t('Participant'),
      'help' => $this->t('Participant of this thread.'),
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
