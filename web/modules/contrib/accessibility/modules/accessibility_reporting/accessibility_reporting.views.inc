<?php

/**
 * @file
 * Provide views data and handlers for accessibility_reporting.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_data().
 */
function accessibility_reporting_views_data() {
  // Define the base group of this table.
  $data['accessibility_reporting']['table']['group']  = t('Accessibility reporting');

  $data['accessibility_reporting']['field'] = array(
    'title' => t('Responsible field'),
    'help'  => t('The field in the entity that had the error.'),
    'field' => array(
      'id' => 'accessibility_reporting_field',
    ),
    'argument' => array(
      'id' => 'string',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['accessibility_reporting']['total'] = array(
    'title' => t('Total errors'),
    'help'  => t('Total number of errors in a field.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['accessibility_reporting']['accessibility_test'] = array(
    'title' => t('Accessibility test'),
    'help'  => t('The accessibility test that failed.'),
    'relationship' => array(
      'label' => t('Accessibility test'),
      'title' => t('Accessibility test'),
      'base' => 'accessibility_test',
      'base field' => 'test_id',
      'field' => 'test_id',
      'id' => 'standard',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function accessibility_reporting_views_data_alter(&$data) {
  // Load information for entity types so we can find their base tables
  $entity_infos = Drupal::service('plugin.manager.entity')->getDefinitions();
  foreach ($entity_infos as $entity_type => $entity_info) {
    if (isset($entity_info['base_table'])) {
      $data[$entity_info['base_table']]['accessibility_reporting_base_left_' . $entity_info['base_table']] = array(
        'title' => t('Accessibility reporting results'),
        'help' => t('Provides a relationship from entities to the appropriate accessibility reporting'),
        'relationship' => array(
          'label' => t('Reporting'),
          'title' => t('Accessibility report results'),
          'base' => 'accessibility_reporting',
          'base field' => 'entity_id',
          'field' => $entity_info['entity_keys']['id'],
          'entity_type_left' => $entity_type,
          'id' => 'standard',
        ),
      );
    }
  }
  
}