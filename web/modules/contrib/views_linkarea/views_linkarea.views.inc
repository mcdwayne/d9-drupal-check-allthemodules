<?php

/**
 * @file
 * Provide views data for views_linkarea
 */

/**
 * Implements hook_views_data().
 */
function views_linkarea_views_data() {
  $data = [];
  $data['views']['linkarea'] = [
    'title' => t('Link'),
    'help' => t('Provide an internal or external link.'),
    'area' => [
      'id' => 'linkarea',
    ],
  ];
  return $data;
}
