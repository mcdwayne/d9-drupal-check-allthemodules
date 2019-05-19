<?php

/**
 * @file
 * Statistics Counter: Views support
 */

/**
 * Implements hook_views_data_alter().
 */
function statistics_counter_views_data_alter(&$data) {
  if (!isset($data['node_counter'])) {
    return;
  }

  // Week counts.
  $data['node_counter']['weekcount'] = array(
    'title' => t('Views this week'),
    'help' => t('The total number of times the node has been viewed this week.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Month counts.
  $data['node_counter']['monthcount'] = array(
    'title' => t('Views this month'),
    'help' => t('The total number of times the node has been viewed this month.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // Year counts.
  $data['node_counter']['yearcount'] = array(
    'title' => t('Views this year'),
    'help' => t('The total number of times the node has been viewed this year.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
}
