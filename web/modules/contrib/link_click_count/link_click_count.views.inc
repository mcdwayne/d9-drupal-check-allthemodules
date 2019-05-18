<?php

/**
 * @file
 * Views file.
 */

/**
 * Implements hook_views_data().
 */
function link_click_count_views_data() {
  $data = [];
  $data['link_click_count']['table'] = [];
  $data['link_click_count']['table']['group'] = t('Link Click Count');
  $data['link_click_count']['table']['provider'] = 'link_click_count';
  $data['link_click_count']['table']['base'] = [
    'field' => 'nid',
    'title' => t('Link count'),
    'help' => t('This table is containing the data about count view of node.'),
    'weight' => -10,
  ];

  $data['link_click_count']['table']['join'] = [
    'node_field_data' => [
      'left_field' => 'nid',
      'field' => 'nid',
      'extra' => [
        0 => [
          'field' => 'published',
          'value' => TRUE,
        ],
        1 => [
          'left_field' => 'numeric_field',
          'value' => 1,
          'numeric' => TRUE,
        ],
        2 => [
          'field' => 'published',
          'left_field' => 'boolean_field',
          'operator' => '!=',
        ],
      ],
    ],
  ];

  $data['link_click_count']['nid'] = [
    'title' => t('Link Node id'),
    'help' => t('Link Node id.'),
    'relationship' => [
      'base' => 'node',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('Link Node id.. node..!'),
    ],
  ];

  $data['link_click_count']['url'] = [
    'title' => t('Link url'),
    'help' => t('url.'),
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

  $data['link_click_count']['date'] = [
    'title' => t('Link click date'),
    'help' => t('When user view of date.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  return $data;
}
