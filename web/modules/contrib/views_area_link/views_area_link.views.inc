<?php

/**
 * @file
 * Provide views data for our views_area_link.
 */

/**
 * Implements hook_views_data().
 */
function views_area_link_views_data() {
  $data = [];
  $data['views']['area_link'] = [
    'title' => t('Link'),
    'help' => t('Provide an internal or external link.'),
    'area' => [
      'id' => 'area_link',
    ],
  ];
  return $data;
}
