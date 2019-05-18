<?php

/**
 * Implements hook_views_data().
 */
function contextual_filter_area_views_data() {
  $data['views']['area_contextual_filter'] = [
    'title' => t('Contextual filter'),
    'help' => t('Insert a contextual filter browser inside an area.'),
    'area' => [
      'id' => 'contextual_filter',
    ],
  ];

  return $data;
}
