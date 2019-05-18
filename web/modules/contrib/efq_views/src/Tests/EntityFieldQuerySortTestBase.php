<?php

/**
 * @file
 * Tests for EFQ Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsSortTestBase
 *
 * @package Drupal\efq_views\Tests
 */
abstract class EntityFieldQueryViewsSortTestBase extends EntityFieldQueryViewsTestBase {

  protected $fields = array(
    array(
      'field_name' => 'field_integer',
      'type' => 'list_integer',
      'settings' => array('allowed_values' => array(5, 6, 7, 8, 9)),
    ),
  );

  protected $instances = array(
    array(
      'field_name' => 'field_integer',
      'entity_type' => 'efq_views_test',
      'bundle' => 'bundle1',
      'label' => 'field integer',
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
      'field_integer' => array(LANGUAGE_NONE => array(array('value' => 7))),
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
      'field_integer' => array(LANGUAGE_NONE => array(array('value' => 5))),
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
      'field_integer' => array(LANGUAGE_NONE => array(array('value' => 8))),
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
      'field_integer' => array(LANGUAGE_NONE => array(array('value' => 6))),
    ),
  );

  protected function map($order) {
    $return = array();
    foreach ($order as $index) {
      $return[] = (int) $this->entities[$index]->eid;
    }
    return $return;
  }

}
