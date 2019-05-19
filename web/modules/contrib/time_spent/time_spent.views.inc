<?php

/**
 * @file
 * Views plugin settings in this file.
 */

/**
 * Implements hook_views_data().
 */
function time_spent_views_data() {
  $data = array();

  // Create a new group by which the of this module plugins would be grouped.
  $data['time_spent_page']['table']['group'] = t('Time Spent');

  $data['time_spent_page']['table']['provider'] = 'time_spent';

  $data['time_spent_page']['table']['base'] = array(
    'field' => 'time_spent_id',
    'title' => t('Time spent'),
    'defaults' => array(
      'field' => 'time_spent_id',
    ),
  );

  $data['time_spent_page']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['time_spent_page']['uid'] = array(
    'title' => t('User ID'),
    'help' => t('Time spent user ID.'),
    'relationship' => array(
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Timespent user'),
    ),
  );

  $data['time_spent_page']['time_spent_id'] = array(
    'title' => t('Time spent id'),
    'help' => t('Unique id representing a time_spent entry'),
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

  $data['time_spent_page']['timespent'] = array(
    // The item it appears as on the UI.
    'title' => t('Time spent length'),
    'help' => t('The timespent length'),
    'field' => array(
      'id' => 'time_spent',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
