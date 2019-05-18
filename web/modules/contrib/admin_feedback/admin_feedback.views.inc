<?php
/**
 * @file
 * Feedback views data and tables.
 */

/**
 * Implements hook_views_data().
 */
function admin_feedback_views_data() {

  $data = [];
  $data['admin_feedback_score'] = [];
  $data['admin_feedback_score']['table'] = [];
  $data['admin_feedback_score']['table']['group'] = t('Admin feedback score');

  $data['admin_feedback_score']['table']['base'] = [
    'field' => 'id',
    'title' => t('Admin feedback score'),
    'help' => t(''),
  ];
  $data['admin_feedback_score']['table']['provider'] = 'admin_feedback';

  $data['admin_feedback_score']['id'] = [
    'title' => t('ID'),
    'help' => t('ID'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback_score']['nid'] = [
    'title' => t('Nodes id'),
    'help' => t('Nodes id'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback_score']['yes_count'] = [
    'title' => t('Nodes positives feedbacks count'),
    'help' => t('Nodes positives feedbacks count'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback_score']['no_count'] = [
    'title' => t('Nodes negative feedbacks count'),
    'help' => t('Nodes negative feedbacks count'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback_score']['count'] = [
    'title' => t('Total count of feedbacks'),
    'help' => t('Total count of feedbacks'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback_score']['total_score'] = [
    'title' => t('Nodes total score'),
    'help' => t('Nodes total score'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback_score']['nid'] = [
    'title' => t('node field data table'),
    'help' => t('Nodes table'),
    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('admin feedback score'),
    ],
  ];

  $data['admin_feedback'] = [];
  $data['admin_feedback']['table'] = [];
  $data['admin_feedback']['table']['group'] = t('Admin feedback');
  $data['admin_feedback']['id'] = [
    'title' => t('ID'),
    'help' => t('ID'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback']['nid'] = [
    'title' => t('nid'),
    'help' => t('nid'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback']['feedback_type'] = [
    'title' => t('feedback_type'),
    'help' => t('feedback_type'),
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
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback']['feedback_message'] = [
    'title' => t('Feedback message'),
    'help' => t('Feedback message.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  $data['admin_feedback']['inspected'] = [
    'title' => t('Inspected'),
    'help' => t('Has this been inspected?'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback']['created'] = [
    'title' => t('Created'),
    'help' => t('Creation date in unix timestamp'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  $data['admin_feedback_score']['feedback_nid'] = [
    'title' => t('admin feedback'),
    'help' => t('admin feedback'),
    'relationship' => [
      'base' => 'admin_feedback',
      'base field' => 'nid',
      'id' => 'standard',
      'field' => 'nid',
      'label' => t('admin feedback'),
    ],
  ];

  return $data;
}

