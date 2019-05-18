<?php

/**
 * @file
 * Tests for EFQ Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsFilterTest
 *
 * @package Drupal\efq_views\Tests
 */
class EntityFieldQueryViewsFilterTest extends EntityFieldQueryViewsTestBase {

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
      'field_boolean' => array(LANGUAGE_NONE => array(array('value' => 1))),
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
      'field_boolean' => array(LANGUAGE_NONE => array(array('value' => 0))),
    ),
  );

  static function getInfo() {
    return array(
      'name' => 'EFQ Views filters',
      'description' => 'Tests EFQ Views filter handlers',
      'group' => 'EFQ Views',
    );
  }

  function testFilters() {
    // Boolean property.
    foreach (array('1', '0') as $key => $value) {
      $view = $this->view();
      $handler = $view->display['default']->handler;
      $handler->display->display_options['filters']['test_boolean']['id'] = 'test_boolean';
      $handler->display->display_options['filters']['test_boolean']['table'] = 'efq_efq_views_test';
      $handler->display->display_options['filters']['test_boolean']['field'] = 'test_boolean';
      $handler->display->display_options['filters']['test_boolean']['value'] = $value;
      $this->runView($view);
      $this->assertEqual(count($view->result), 1);
      $entity = $this->entities[$key];
      $this->assertPattern("/Entity ID: +$entity->eid/", 'Entity ID found');
    }
    // Integer property. Each test array is a list of: value, operator,
    // entity keys to be found.
    $tests = array(
      array('15', '=', 0),
      array('16', '='),
      array('15', NULL, 0),
      array('16', NULL),
      array('15', '>', 1),
      array('15', '<'),
      array('15', '<=', 0),
      array('15', '>=', 0, 1),
      array('15', '<>', 1),
      array('17', '<', 0),
      array('17', '<=', 0, 1),
      array(array('16', '17'), 'BETWEEN', 1),
      array(array('12', '18'), 'BETWEEN', 0, 1),
    );
    foreach ($tests as $test) {
      $value = array_shift($test);
      if (is_scalar($value)) {
        $value = array('value' => $value);
      }
      else {
        $value = array('min' => $value[0], 'max' => $value[1]);
      }
      $operator = array_shift($test);
      $this->pass(strtr("test @value $operator", array('@value' => implode(' ', $value))));
      $view = $this->view();
      $handler = $view->display['default']->handler;
      $handler->display->display_options['filters']['test_integer']['id'] = 'test_integer';
      $handler->display->display_options['filters']['test_integer']['table'] = 'efq_efq_views_test';
      $handler->display->display_options['filters']['test_integer']['field'] = 'test_integer';
      if (isset($operator)) {
        $handler->display->display_options['filters']['test_integer']['operator'] = $operator;
      }
      $handler->display->display_options['filters']['test_integer']['value'] = $value;
      $this->runView($view);
      $this->assertEqual(count($view->result), count($test));
      foreach ($test as $key) {
        $entity = $this->entities[$key];
        $this->assertPattern("/Entity ID: +$entity->eid/", 'Entity ID found');
      }
    }

    $view = $this->view();
    $handler = $view->display['default']->handler;
    $handler->display->display_options['arguments']['test_integer']['id'] = 'test_integer';
    $handler->display->display_options['arguments']['test_integer']['table'] = 'efq_efq_views_test';
    $handler->display->display_options['arguments']['test_integer']['field'] = 'test_integer';
    $view->set_arguments(array(15));
    $this->runView($view);
    $this->assertEqual(count($view->result), 1);
    $entity = $this->entities[0];
    $this->assertPattern("/Entity ID: +$entity->eid/", 'Entity ID found');
    $view = $this->view();
    $handler = $view->display['default']->handler;
    $handler->display->display_options['arguments']['test_integer']['id'] = 'test_integer';
    $handler->display->display_options['arguments']['test_integer']['table'] = 'efq_efq_views_test';
    $handler->display->display_options['arguments']['test_integer']['field'] = 'test_integer';
    $handler->display->display_options['arguments']['test_integer']['break_phrase'] = TRUE;
    $view->set_arguments(array('15+17'));
    $this->runView($view);
    $this->assertEqual(count($view->result), 2);
    foreach (array(0, 1) as $eid) {
      $entity = $this->entities[$eid];
      $this->assertPattern("/Entity ID: +$entity->eid/", 'Entity ID found');
    }
    // Integer-stored list boolean.
    foreach (array('1', '0') as $key => $value) {
      foreach (array('filters', 'arguments') as $test_type) {
        $view = $this->view();
        $handler = $view->display['default']->handler;
        $handler->display->display_options[$test_type]['field_boolean']['id'] = 'field_boolean';
        $handler->display->display_options[$test_type]['field_boolean']['table'] = 'efq_efq_views_test';
        $handler->display->display_options[$test_type]['field_boolean']['field'] = 'field_boolean';
        if ($test_type == 'filters') {
          $handler->display->display_options[$test_type]['field_boolean']['value'] = array($value);
        }
        else {
          $view->set_arguments(array($value));
        }
        $this->runView($view);
        $this->assertEqual(count($view->result), 1);
        $entity = $this->entities[$key];
        $this->assertPattern("/Entity ID: +$entity->eid/", 'Entity ID found');
      }
    }
    // Label. Each test array is a list of: value, operator, entity keys to
    // be found.
    $tests = array(
      array('test', 'STARTS_WITH', 0),
      array('label', 'CONTAINS', 0, 1),
      array('banana', 'STARTS_WITH'),
      array('banana', 'CONTAINS'),
      array('test label 1', '=', 0),
      array('test label 1', NULL, 0),
      array('test label 2', '='),
      array('test label 2', NULL),
    );
    foreach ($tests as $test) {
      $value = array_shift($test);
      $operator = array_shift($test);
      $this->pass("test $value $operator");
      $view = $this->view();
      $handler = $view->display['default']->handler;
      $handler->display->display_options['filters']['label']['id'] = 'label';
      $handler->display->display_options['filters']['label']['table'] = 'efq_efq_views_test';
      $handler->display->display_options['filters']['label']['field'] = 'label';
      if (isset($operator)) {
        $handler->display->display_options['filters']['label']['operator'] = $operator;
      }
      $handler->display->display_options['filters']['label']['value'] = $value;
      $this->runView($view);
      $this->assertEqual(count($view->result), count($test));
      foreach ($test as $key) {
        $entity = $this->entities[$key];
        $this->assertPattern("/Entity ID: +$entity->eid/", 'Entity ID found');
      }
    }
  }

}
