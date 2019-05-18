<?php

/**
 * @file
 * Contains ajax_link_change\ajax_link_change.views.inc.
 *
 * Provide a ajax toggle switch field .
 *
 * You can change the value of field with ajax link.
 */

/**
 * Implements hook_views_data().
 */
function ajax_link_change_views_data() {
  $data['views']['table']['group'] = t('Ajax link Change');
  $data['views']['table']['join'] = [
      // #global is a special flag which allows a table to appear all the time.
    '#global' => [],
  ];
  $data['views']['ajax_link_change_views_field'] = [
    'title' => t('Ajax link Change'),
    'help' => t('Change value of field with ajax'),
    'field' => [
      'id' => 'ajax_link_change_views_field',
    ],
  ];

  return $data;
}
