<?php

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Implements hook_views_data().
 */
function node_access_timestamp_by_user_views_data() {

  $data['node_access_timestamp_by_user'] = [];
  $data['node_access_timestamp_by_user']['table'] = [];
  $data['node_access_timestamp_by_user']['table']['group'] = t('Node Access Timestamp by User');
  $data['node_access_timestamp_by_user']['table']['provider'] = 'node_access_timestamp_by_user';

  $data['node_access_timestamp_by_user']['table']['base'] = [
    'field' => 'timestamp',
    'title' => t('Node Access Timestamp by User'),
    'help' => t('Node Access Timestamp by User table base.'),
    'weight' => -10,
  ];

  $data['node_access_timestamp_by_user']['table']['join'] = [
    'users_field_data' => [
      'left table' => 'node_field_data',
      'left_field' => 'nid',
      'field' => 'nid',
    ],
  ];

  // UID field.
  $data['node_access_timestamp_by_user']['uid'] = [
    'title' => t('UID'),
    'click sortable' => TRUE,
    'field' => [
      'title' => t('UID Field'),
      'label' => t('UID'),
      'help' => t('UID field from node_access_timestamp_by_user.'),
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => 'UID Relationship',
      'label' => t('UID'),
      'help' => t('UID relationship from node_access_timestamp_by_user.'),
      'id' => 'standard',
      'base' => 'users_field_data',
      'base field' => 'uid',
      'relationship field' => 'uid',
    ],
    'argument' => [
      'title' => t('UID Argument'),
      'label' => t('UID'),
      'help' => t('UID argument from node_access_timestamp_by_user.'),
      'id' => 'numeric',
    ],
    'filter' => [
      'title' => t('UID Filter'),
      'label' => t('UID'),
      'help' => t('UID filter from node_access_timestamp_by_user.'),
      'id' => 'numeric',
    ],
    'sort' => [
      'title' => t('UID Sort'),
      'label' => t('UID'),
      'help' => t('UID sort from node_access_timestamp_by_user.'),
      'id' => 'standard',
    ],
  ];

  // NID field.
  $data['node_access_timestamp_by_user']['nid'] = [
    'title' => t('NID'),
    'click sortable' => TRUE,
    'float' => TRUE,
    'field' => [
      'title' => t('NID Field'),
      'label' => t('NID'),
      'help' => t('NID field from node_access_timestamp_by_user.'),
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => 'NID Relationship',
      'label' => t('NID'),
      'help' => t('NID relationship from node_access_timestamp_by_user.'),
      'id' => 'standard',
      'base' => 'node_field_data',
      'base field' => 'nid',
      'relationship field' => 'nid',
    ],
    'argument' => [
      'title' => t('NID Argument'),
      'label' => t('NID'),
      'help' => t('NID argument from node_access_timestamp_by_user.'),
      'id' => 'numeric',
    ],
    'filter' => [
      'title' => t('NID Filter'),
      'label' => t('NID'),
      'help' => t('NID filter from node_access_timestamp_by_user.'),
      'id' => 'numeric',
    ],
    'sort' => [
      'title' => t('NID Sort'),
      'label' => t('NID'),
      'help' => t('NID sort from node_access_timestamp_by_user.'),
      'id' => 'standard',
    ],
  ];

  // Timestamp field.
  $data['node_access_timestamp_by_user']['timestamp'] = [
    'title' => t('Timestamp'),
    'click sortable' => TRUE,
    'field' => [
      'title' => t('Timestamp Field'),
      'label' => t('Timestamp'),
      'help' => t('Timestamp field from node_access_timestamp_by_user.'),     
      'id' => 'date',
    ],
    'filter' => [
      'title' => t('Timestamp Filter'),
      'label' => t('Timestamp'),
      'help' => t('Timestamp filter from node_access_timestamp_by_user.'),
      'id' => 'date',
    ],
    'sort' => [
      'title' => t('Timestamp Sort'),
      'label' => t('Timestamp'),
      'help' => t('Timestamp sort from node_access_timestamp_by_user.'),
      'id' => 'date',
    ],
  ];

  // Bundle field.
  $data['node_access_timestamp_by_user']['bundle'] = [
    'title' => t('Bundle'),
    'click sortable' => TRUE,
    'field' => [
      'title' => t('Bundle Field'),
      'label' => t('Bundle'),
      'help' => t('Bundle field from node_access_timestamp_by_user.'),
      'id' => 'machine_name',
    ],
    'filter' => [
      'title' => t('Bundle Filter'),
      'label' => t('Bundle'),
      'help' => t('Bundle filter from node_access_timestamp_by_user.'),
      'id' => 'string',
    ],
    'sort' => [
      'title' => t('Bundle Sort'),
      'label' => t('Bundle'),
      'help' => t('Bundle sort from node_access_timestamp_by_user.'),
      'id' => 'standard',
    ],
  ];

  // Active User Flag field. This field does not exist in database.
  // See /node_access_timestamp_by_user/src/Plugin/views/field/ActiveUserFlag.php.
  $data['node_access_timestamp_by_user']['active_user_flag'] = [
    'title' => t('Active User Flag'),
    'click sortable' => TRUE,
    'field' => [
      'title' => t('Active User Flag Field'),
      'label' => t('Active User Flag'),
      'help' => t('Active User Flag field from node_access_timestamp_by_user.'),
      'field' => 'timestamp',
      'id' => 'active_user_flag',
    ],
    'filter' => [
      'title' => t('Active User Flag Filter'),
      'label' => t('Active User Flag'),
      'help' => t('Active User Flag filter from node_access_timestamp_by_user.'),
      'field' => 'timestamp',
      'id' => 'string',
    ],
  ];

  return $data;

}
