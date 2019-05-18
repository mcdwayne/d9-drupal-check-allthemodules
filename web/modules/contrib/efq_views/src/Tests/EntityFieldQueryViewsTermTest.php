<?php

/**
 * @file
 * Tests for EntityFieldQuery Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsTermTest
 *
 * @package Drupal\efq_views\Tests
 */
class EntityFieldQueryViewsTermTest extends EntityFieldQueryViewsTestBase {

  protected $terms = array();

  protected $map = array(
    array(0),
    array(1),
    array(0, 1),
    array(),
  );

  protected $expectedResults = array(
    // First we test which entities have term 0. on them, that's where you see
    // a 0 in the map, so it's 0th and 2nd.
    array(0, 2),
    // Now we test term 1. That's where you see an 1 in the map, so 1 and 2.
    array(1, 2),
    // Now we test term 0 or 1. That's where you see a 0 or a 1 in the map,
    // so entities 0th, 1st, 2nd. Not the 3rd, however.
    array(0, 1, 2),
  );

  protected $fields = array(
    array(
      'field_name' => 'field_term',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      'settings' => array('allowed_values' => array(array('vocabulary' => 'test_vocab'))),
    ),
  );

  protected $instances = array(
    array(
      'field_name' => 'field_term',
      'entity_type' => 'efq_views_test',
      'bundle' => 'bundle1',
      'label' => 'field term',
    ),
  );

  protected $entities_array = array(
    array(
      'ebundle' => 'bundle1',
      'elabel' => 'test label 1',
      'uid' => 1,
      'test_boolean' => TRUE,
      'test_integer' => 15,
      'test_decimal' => 78,
      'test_duration' => 900,
      'test_date' => 1336236850,
      'test_text' => 'test text',
    ),
    array(
      'ebundle' => 'bundle1',
      'elabel' => 'string label 2',
      'uid' => 1,
      'test_boolean' => FALSE,
      'test_integer' => 17,
      'test_decimal' => 80,
      'test_duration' => 1500,
      'test_date' => 1336366325,
      'test_text' => 'another test text',
    ),
    array(
      'ebundle' => 'bundle1',
      'elabel' => 'test label 3',
      'uid' => 1,
      'test_boolean' => TRUE,
      'test_integer' => 18,
      'test_decimal' => -5,
      'test_duration' => 900,
      'test_date' => 1336234000,
      'test_text' => 'test text',
    ),
    array(
      'ebundle' => 'bundle1',
      'elabel' => 'string label 4',
      'uid' => 1,
      'test_boolean' => FALSE,
      'test_integer' => 19,
      'test_decimal' => 90,
      'test_duration' => 1500,
      'test_date' => 1336266000,
      'test_text' => 'another test text',
    ),
  );

  static function getInfo() {
    return array(
      'name' => 'EntityFieldQuery Views Terms',
      'description' => 'Tests EntityFieldQuery Views taxonomy term integration',
      'group' => 'EntityFieldQuery Views',
    );
  }

  protected function setUp() {
    $this->postponeFields = TRUE;
    parent::setUp();
    $vocabulary = (object) array(
      'name' => 'Test vocabulary',
      'machine_name' => 'test_vocab',
    );
    taxonomy_vocabulary_save($vocabulary);
    for ($i = 0; $i < 2; $i++) {
      $term = (object) array(
        'name' => $this->randomName(),
        'vid' => $vocabulary->vid,
      );
      taxonomy_term_save($term);
      $this->terms[] = $term;
    }
    foreach ($this->entities_array as $key => &$entity_array) {
      $delta = 0;
      foreach ($this->map[$key] as $term_key) {
        $entity_array['field_term'][LANGUAGE_NONE][$delta++]['tid'] = $this->terms[$term_key]->tid;
      }
    }
    $this->saveFieldsEntities();
  }

  protected function testTermFilter() {
    foreach ($this->map as $test_key => $test) {
      if ($test) {
        $view = new view();
        $view->base_table = 'efq_efq_views_test';
        $handler = $view->new_display('default');
        $handler->display->display_options['fields']['entity_id']['id'] = 'entity_id';
        $handler->display->display_options['fields']['entity_id']['table'] = 'efq_efq_views_test';
        $handler->display->display_options['fields']['entity_id']['field'] = 'entity_id';
        $handler->display->display_options['filters']['field_term']['id'] = 'field_term';
        $handler->display->display_options['filters']['field_term']['table'] = 'efq_efq_views_test';
        $handler->display->display_options['filters']['field_term']['field'] = 'field_term';
        foreach ($test as $term_key) {
          // Add a string cast just for kicks.
          $handler->display->display_options['filters']['field_term']['value'][] = (string) $this->terms[$term_key]->tid;
        }
        $handler->display->display_options['filters']['field_term']['vocabulary'] = 'test_vocab';
        $view->execute();
        $ids = $this->getIds($view->result);
        foreach ($this->expectedResults[$test_key] as $expectedResult) {
          $eid = $this->entities[$expectedResult]->eid;
          $this->assertTrue(isset($ids[$eid]));
          unset($ids[$eid]);
        }
        $this->assertFalse($ids);
      }
    }
  }

}