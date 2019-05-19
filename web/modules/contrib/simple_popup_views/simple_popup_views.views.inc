<?php

/**
 * @file
 * Include file for defining views handlers and data.
 */

/**
 * Implements hook_views_data().
 */
function simple_popup_views_views_data() {
  $data['simple_popup_views']['table']['group'] = t('Views');
  $data['simple_popup_views']['table']['join'] = [
    '#global' => [],
  ];

  $data['simple_popup_views']['simple_popup_views_field'] = [
    'title' => t('Simple Popup Views'),
    'field' => [
      'title' => t('Simple Popup Views'),
      'help' => t('Convert views field as popup.'),
      'id' => 'simple_popup_views_field',
    ],
  ];

  return $data;
}
