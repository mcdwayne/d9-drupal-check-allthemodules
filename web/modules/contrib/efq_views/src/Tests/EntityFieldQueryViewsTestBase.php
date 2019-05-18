<?php

/**
 * @file
 * Tests for EFQ Views query features.
 */

namespace Drupal\efq_views\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Abstract class for EFQ views testing.
 *
 * @package Drupal\efq_views\Tests
 */
abstract class EntityFieldQueryViewsTestBase extends WebTestBase {

  protected $postponeFields = FALSE;

  protected $entities_array = array();

  protected $fields = array(
    array(
      'field_name' => 'field_boolean',
      'type' => 'list_boolean',
      'settings' => array('allowed_values' => array('Off', 'On')),
    ),
  );

  protected $instances = array(
    array(
      'field_name' => 'field_boolean',
      'entity_type' => 'efq_views_test',
      'bundle' => 'bundle1',
      'label' => 'field boolean',
    ),
  );

  protected function setUp() {
    if (module_exists('mongodb') || (($module_data = system_rebuild_module_data()) && isset($module_data['mongodb']))) {
      variable_set('field_storage_default', 'mongodb_field_storage');
      parent::setUp('mongodb_field_storage', 'efq_views_test');
      $this->pass('Running MongoDB');
    }
    else {
      parent::setUp('efq_views_test');
      $this->pass('Running MySQL');
    }
    if (empty($this->postponeFields)) {
      $this->saveFieldsEntities();
    }
  }

  protected function saveFieldsEntities() {
    foreach ($this->fields as $field) {
      field_create_field($field);
    }
    foreach ($this->instances as $instance) {
      field_create_instance($instance);
    }
    // Load the test dataset.
    foreach ($this->entities_array as $key => $entity_array) {
      $entity = entity_create('efq_views_test', $entity_array);
      entity_save('efq_views_test', $entity);
      $this->assertFalse(empty($entity->eid), 'Entity saved');
      $this->entities[$key] = $entity;
    }
  }

  protected function view() {
    $view = new view();
    $view->base_table = 'efq_efq_views_test';
    /* Display: Master */
    $handler = $view->new_display('default');
    $handler->display->display_options['row_options']['default_field_elements'] = FALSE;
    $fields = array(
      'entity_id',
      'eid',
      'language',
      'test_boolean',
      'test_date',
      'test_decimal',
      'test_duration',
      'test_integer',
      'test_text',
      'uid',
      'url',
      'field_boolean',
      'bundle',
      'bundle_label',
      'label',
    );
    foreach ($fields as $field) {
      $handler->display->display_options['fields'][$field] = array(
        'id' => $field,
        'field' => $field,
        'table' => 'efq_efq_views_test',
      );
    }
    return $view;
  }

  protected function runView($view = NULL) {
    if (!isset($view)) {
      $view = $this->view();
    }
    $content = $view->preview();
    $this->verbose($content);
    $this->drupalSetContent($content);
  }

  protected function getIds($view_result) {
    $ids = array();
    foreach ($view_result as $result) {
      $ids[$result->entity_id] = $result->entity_id;
    }
    return $ids;
  }

}