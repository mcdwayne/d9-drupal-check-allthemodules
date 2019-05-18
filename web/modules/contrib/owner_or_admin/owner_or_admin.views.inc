<?php

/**
 * @file
 * Contains owner_or_admin.views.inc.
 */

/**
 * Implements hook_views_data_alter().
 */
function owner_or_admin_views_data_alter(array &$data) {
  $data['users']['uid_owner_or_admin'] = array(
    'title' => t('Owner or Admin'),
    'filter' => array(
      'title' => t('Owner or Admin'),
      'help' => t('Filter the view by entity owner or administrator. Similar to node filter "published or admin".'),
      'field' => 'uid',
      'id' => 'owner_or_admin_filter',
    ),
  );
}