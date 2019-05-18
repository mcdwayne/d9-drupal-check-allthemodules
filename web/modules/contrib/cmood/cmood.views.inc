<?php

/**
 * @file
 * Used to expose nodes mood to views.
 */

/**
 * Implements hook_views_data().
 */
function cmood_views_data() {
  $data = [];
  $data['node_mood']['table']['group'] = t('Node mood');
  $data['node_mood']['table']['base'] = [
    'title' => t('Node mood'),
    'help' => t('Stores nodes with respective mood value'),
  ];
  $data['node_mood']['id'] = [
    'title' => t('ID'),
    'help' => t('The record ID.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];
  $data['node_mood']['mood'] = [
    'title' => t('Mood'),
    'help' => t('The node mood.'),
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
  $data['node_mood']['table']['join'] = [
    'node_field_data' => [
      'left_field' => 'nid',
      'field' => 'nid',
    ],
  ];
  $data['node_mood']['nid'] = [
    'title' => t('Node ID'),
    'help' => t('The node ID.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'base' => 'node_field_data',
      'field' => 'nid',
      'id' => 'standard',
      'label' => t('Node'),
    ],
    'argument' => [
      'id' => 'numeric',
      'numeric' => TRUE,
      'validate type' => 'nid',
    ],
  ];

  return $data;
}
