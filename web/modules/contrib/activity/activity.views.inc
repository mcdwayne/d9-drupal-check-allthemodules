<?php

/**
 * @file
 * Provide views data that display activity actions.
 */

/**
 * Implements hook_views_data().
 */
function activity_views_data() {
  $data = [];
  // Base data.
  $data['activity']['table']['group'] = t('Activity');
  $data['activity']['table']['base'] = [
    'title' => t('All activities'),
    'help' => t('Display all actions.'),
    'query_id' => 'activity',
  ];
  $data['activity']['message'] = [
    'title' => t('Message'),
    'help' => t('Message displayed for action.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['activity']['uid'] = [
    'title' => t('User id'),
    'help' => t('User that created the action.'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['activity']['entity_type'] = [
    'title' => t('Entity type'),
    'help' => t('Entity type.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['activity']['created'] = [
    'title' => t('Created on'),
    'help' => t('Date when action was created.'),
    'field' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
  ];

  $data['activity']['nid'] = [
    'title' => t('Node id'),
    'help' => t('Node id for which the action is triggered.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['activity']['status'] = [
    'title' => t('Status'),
    'help' => t('Entity status.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['activity']['action_id'] = [
    'title' => t('Id'),
    'help' => t('The action id.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['activity']['event_id'] = [
    'title' => t('Event id'),
    'help' => t('The event id.'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['activity']['delete_action'] = [
    'title' => t('Delete action'),
    'help' => t('Delete action.'),
    'field' => [
      'title' => 'Delete action',
      'id' => 'delete_action',
    ],
  ];
  return $data;
}
