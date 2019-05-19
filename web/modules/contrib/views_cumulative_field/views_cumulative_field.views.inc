<?php

/**
 * @file
 * Implements hook_views_data().
 */

/**
 * Implements hook_views_data().
 */
function views_cumulative_field_views_data() {
  $data['views_cumulative_field']['table']['group'] = t('Global');
  $data['views_cumulative_field']['table']['join'] = [
    // Exist in all views.
    '#global' => [],
  ];
  $data['views_cumulative_field']['field_cumulative_field'] = [
    'title' => t('Cumulative Field'),
    'help' => t('Views field that calculates the cumulative value of another field in your view.'),
    'field' => [
      'id' => 'field_cumulative_field',
    ],
  ];
  return $data;
}
