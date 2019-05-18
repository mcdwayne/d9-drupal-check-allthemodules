<?php

/**
 * @file
 * Views integration functions for the private module.
 */

/**
 * Implements hook_views_data().
 */
function private_views_data() {
  $data = array();

  $data['private']['table']['group'] = t('Content');

  $data['private']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
    'private' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['private']['private'] = array(
    'title' => t('Private'),
    'help' => t('Whether or not the node is private.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'private_handler_filter_private',
      'label' => t('Private'),
      'type' => 'yes-no',
    ),
  );

  return $data;

}
