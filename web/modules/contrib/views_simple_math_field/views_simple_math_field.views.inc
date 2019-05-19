<?php

/**
 * @file
 * Implements hook_views_data().
 */

/**
 * Implements hook_views_data().
 */
function views_simple_math_field_views_data() {
  $data['views_simple_math_field']['table']['group'] = t('Global');
  $data['views_simple_math_field']['table']['join'] = [
    // Exist in all views.
    '#global' => [],
  ];
  $data['views_simple_math_field']['field_views_simple_math_field'] = [
    'title' => t('Simple Math Field'),
    'help' => t('Views field that performs simple math expressions.'),
    'field' => [
      'id' => 'field_views_simple_math_field',
      // Allow users to specify how precise the results should be. Useful for divisions
      'float' => TRUE,
    ],
    'sort' => [
      'id' => 'views_simple_math_field_sort',
    ],
  ];
  return $data;
}
