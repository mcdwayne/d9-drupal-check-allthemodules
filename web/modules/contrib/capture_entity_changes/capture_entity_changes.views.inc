<?php

/**
 * @file
 * Views.
 *
 * Views for capture_entity_changes.
 */

/**
 * Implements hook_views_data().
 */
function capture_entity_changes_views_data() {

  $data = [];

  $data['cec_audit'] = [];
  $data['cec_audit']['table'] = [];
  $data['cec_audit']['table']['group'] = t('Entity Changes');
  $data['cec_audit']['table']['provider'] = 'capture_entity_changes_module';

  $data['cec_audit']['table']['base'] = [
    'field' => 'id',
    'title' => t('Capture Entity Changes'),
    'help' => '',
    'weight' => -10,
  ];

  $data['capture_entity_changes']['table']['join'] = [
    'node_field_data' => [
      'left_field' => 'id',
      'field' => 'id',
      'extra' => [
        0 => [
          'left_field' => 'field_name',
          'operator' => '=',
        ],
        1 => [
          'left_field' => 'before_value',
          'operator' => '=',
        ],
        2 => [
          'left_field' => 'after_value',
          'operator' => '=',
        ],
        3 => [
          'left_field' => 'group',
          'operator' => '=',
        ],
      ],
    ],
  ];

  $data['cec_audit']['table']['join']['node_field_data'] = [
    'left_table' => 'foo',
    'left_field' => 'id',
    'field' => 'id',
    'extra' => [],
  ];

  $data['cec_audit']['id'] = [
    'title' => t('Id (autoincrement)'),
    'help' => '',

    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'id',
      'id' => 'standard',
      'label' => t('Example node'),
    ],
  ];

  $data['cec_audit']['field_name'] = [
    'title' => t('Field Name'),
    'help' => '',
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

  $data['cec_audit']['before_value'] = [
    'title' => t('Before Value'),
    'help' => '',
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

  $data['cec_audit']['after_value'] = [
    'title' => t('After Value'),
    'help' => '',
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

  $data['cec_audit']['type'] = [
    'title' => t('Type'),
    'help' => '',
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

  $data['cec_audit']['bundle'] = [
    'title' => t('Bundle'),
    'help' => '',
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

  $data['cec_audit']['uid'] = [
    'title' => t('User Id'),
    'help' => '',
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['cec_audit']['ip'] = [
    'title' => t('Remote IP adress.'),
    'help' => '',
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];

  $data['cec_audit']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('Just a timestamp field.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['cec_audit']['group'] = [
    'title' => t('Group'),
    'help' => '',
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];

  $data['views']['area'] = [
    'title' => t('Text area'),
    'help' => t('Provide markup text for the area.'),
    'area' => [
      'id' => 'text',
    ],
  ];

  return $data;
}
