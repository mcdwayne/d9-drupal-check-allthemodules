<?php

/**
 * @file
 * Provide views data for google_analytics_counter.module.
 */

/**
 * Implements hook_views_data().
 */
function google_analytics_counter_views_data() {
  // Define the return array.
  $data = [];

  $data['google_analytics_counter_storage'] = [];
  $data['google_analytics_counter_storage']['table'] = [];
  $data['google_analytics_counter_storage']['table']['group'] = t('Google Analytics Counter');
  $data['google_analytics_counter_storage']['table']['provider'] = 'google_analytics_counter';
  $data['google_analytics_counter_storage']['table']['base'] = [
    'field' => 'nid',
    'title' => t('Google Analytics Counter'),
    'help' => t('Google Analytics Counter module table containing pageview counts.'),
    'weight' => -10,
  ];

  $data['google_analytics_counter_storage']['nid'] = [
    'title' => t('Node field data'),
    'help' => t('Relate Google Analytics Counter content to the node content'),
    'relationship' => [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('nodes'),
    ],
  ];

  // Numeric field, exposed as a field, sort, filter, and argument.
  $data['google_analytics_counter_storage']['pageview_total'] = [
    'title' => t('Google Analytics Counter Pageviews total'),
    'help' => t('Total pageview counts'),
    'field' => [
      // ID of field handler plugin to use.
      'id' => 'numeric',
    ],
    'sort' => [
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ],
    'filter' => [
      // ID of filter handler plugin to use.
      'id' => 'numeric',
    ],
    'argument' => [
      // ID of argument handler plugin to use.
      'id' => 'numeric',
    ],
  ];

  return $data;
}
