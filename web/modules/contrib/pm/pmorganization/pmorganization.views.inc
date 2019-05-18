<?php
/**
 * @file
 * Views integration settings.
 */

/**
 * Implements hook_views_data().
 */
function pmorganization_views_data() {

  $data = array();
  $data['pmorganization_index']['table']['group'] = t('Drupal PM');
  $data['pmorganization_index']['table']['base'] = array(
    'title' => t('PM Organization'),
  );

  $data['pmorganization_index']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // The pmorganization_nid field.
  $data['pmorganization_index']['pmorganization_nid'] = array(
    'title' => t('PM Organization'),
    'help' => t('PM Organization the content is attached to.'),
    'relationship' => array(
      'base' => 'node',
      'field' => 'pmorganization_nid',
      'handler' => 'views_handler_relationship',
      'label' => t('PM Organization'),
    ),
  );
  return $data;
}
