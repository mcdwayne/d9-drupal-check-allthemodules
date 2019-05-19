<?php

/**
 * @file
 * Views plugin settings in this file.
 */

/**
 * Implements hook_views_data().
 */
function time_spent_all_views_data() {
  $data = array();

  // Create a new group by which the of this module plugins would be grouped.
  $data['time_spent_all_page']['table']['group'] = t('Time Spent All');

  $data['time_spent_all_page']['table']['provider'] = 'time_spent_all';

  $data['time_spent_all_page']['table']['base'] = array(
    'field' => 'time_spent_all_id',
    'title' => t('Time spent All'),
    'defaults' => array(
      'field' => 'time_spent_all_id',
    ),
  );

  $data['time_spent_all_page']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['time_spent_all_page']['uid'] = array(
    'title' => t('User ID'),
    'help' => t('Time spent All user ID.'),
    'relationship' => array(
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Timespentall user'),
    ),
  );

  $data['time_spent_all_page']['time_spent_all_id'] = array(
    'title' => t('Time spent id'),
    'help' => t('Unique id representing a time_spent_all entry'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['time_spent_all_page']['timespent'] = array(
    // The item it appears as on the UI.
    'title' => t('Time spent length'),
    'help' => t('The timespent length'),
    'field' => array(
      'id' => 'time_spent_all',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
