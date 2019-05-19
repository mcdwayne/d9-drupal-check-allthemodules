<?php

/**
 * @file
 * The views.inc file.
 */

/**
 * Implements hook_views_data().
 */
function views_field_percentage_views_data() {

  $data['views']['table']['group'] = t('Custom Global');
  $data['views']['table']['join'] = [
    '#global' => [],
  ];

  $data['views']['percentage_views_field'] = [
    'title' => t('Percentage views field'),
    'help' => t('Display numeric Field as Percentage'),
    'field' => [
      'id' => 'percentage_views_field',
    ],
  ];

  return $data;
}
