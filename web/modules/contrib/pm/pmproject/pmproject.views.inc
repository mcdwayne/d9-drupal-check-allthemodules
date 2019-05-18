<?php
/**
 * @file
 * Views integration settings.
 */

/**
 * Implements hook_views_data().
 */
function pmproject_views_data() {

  $data = array();
  $data['pmproject_index']['table']['group'] = t('Drupal PM');
  $data['pmproject_index']['table']['base'] = array(
    'title' => t('PM Project'),
  );

  $data['pmproject_index']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // The pmproject_nid field.
  $data['pmproject_index']['pmproject_nid'] = array(
    'title' => t('PM Project'),
    'help' => t('PM Project the content is attached to.'),
    'relationship' => array(
      'base' => 'node',
      'field' => 'pmproject_nid',
      'handler' => 'views_handler_relationship',
      'label' => t('PM Project'),
    ),
  );
  return $data;
}
