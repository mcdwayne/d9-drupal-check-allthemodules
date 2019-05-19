<?php

/**
 * @file
 * Implements hook_views_data().
 */

/**
 * Implements hook_views_data().
 */
function views_raw_sql_views_data() {
  $data['views_raw_sql']['table']['group'] = t('Global');
  $data['views_raw_sql']['table']['join'] = [
    // Exist in all views.
    '#global' => [],
  ];
  $data['views_raw_sql']['raw_sql_field'] = [
    'title' => t('Numeric Raw SQL field'),
    'help' => t('Provide raw SQL field expression to produce a number.'),
    'field' => [
      'id' => 'field_views_raw_sql_numeric',
    ],
  ];
  $data['views_raw_sql']['raw_sql_filter'] = [
    'title' => t('Raw Filter'),
    'help' => t('Provide raw SQL filter.'),
    'filter' => [
      'id' => 'filter_views_raw_sql',
    ],
  ];
  $data['views_raw_sql']['raw_sql_sort'] = [
    'title' => t('Raw Sort'),
    'help' => t('Provide raw SQL sort.'),
    'sort' => [
      'id' => 'sort_views_raw_sql',
    ],
  ];
  $data['views_raw_sql']['raw_sql_argument'] = [
    'title' => t('Raw Argument'),
    'help' => t('Provide raw SQL contextual filter.'),
    'argument' => [
      'id' => 'argument_views_raw_sql',
    ],
  ];

  return $data;
}
