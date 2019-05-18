<?php

namespace Drupal\past_db;

use Drupal\views\EntityViewsData;

/**
 * Provides PastEvent-related integration information for Views.
 */
class PastEventViewsData extends EntityViewsData {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['past_event']['trace_user'] = [
      'title' => t('Trace user'),
      'help' => t('Add links to sort the view by user / session id.'),
      'real field' => 'uid',
      'field' => [
        'id' => 'past_db_trace_user',
      ],
    ];

    $data['past_event_join_argument']['table']['group']  = t('Past event argument');

    // Automatically join to the past_event table.
    // Use a Views table alias to allow other modules to use this table too.
    $data['past_event_join_argument']['table']['join'] = [
      'past_event' => [
        'left_field' => 'event_id',
        'field' => 'event_id',
        'table' => 'past_event_argument',
      ],
    ];

    $data['past_event_value']['table']['group']  = t('Past event data');

    // Join with the alias of the table generated in the
    // past_event_join_argument join.
    $data['past_event_value']['table']['join'] = [
      'past_event' => [
        'left_field' => 'argument_id',
        'field' => 'argument_id',
        'left_table' => 'past_event_join_argument',
        'table' => 'past_event_data',
      ],
    ];


    $data['past_event_join_argument']['name'] = [
      'title' => t('Argument name'),
      'help' => t('Filter by a specific argument name'),
      'filter' => [
        'id' => 'string',
      ],
    ];

    $data['past_event_value']['name'] = [
      'title' => t('Data key'),
      'help' => t('Filter by a specific data key'),
      'filter' => [
        'id' => 'string',
      ],
    ];

    $data['past_event']['argument_data'] = [
      'title' => t('Argument data'),
      'help' => t('Display or filter by a specific argument data'),
      'real field' => 'event_id',
      'field' => [
        'id' => 'past_db_event_argument_data',
      ],
      'filter' => [
        'id' => 'past_db_event_argument_data',
      ],
    ];

    return $data;
  }

}
