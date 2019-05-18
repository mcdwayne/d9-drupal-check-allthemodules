<?php

/**
 * @file
 * Tests for EntityFieldQuery Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsSortTest
 *
 * @package Drupal\efq_views\Tests
 */
class EntityFieldQueryViewsSortTest extends EntityFieldQueryViewsSortTestBase {

  /**
   * @param $field
   * @param $expected_result
   */
  function runTestView($field, $expected_result) {
    $view = new view();
    $view->base_table = 'efq_efq_views_test';
    $handler = $view->new_display('default');
    $handler->display->display_options['row_options']['default_field_elements'] = FALSE;
    $handler->display->display_options['fields']['entity_id']['id'] = 'entity_id';
    $handler->display->display_options['fields']['entity_id']['field'] = 'entity_id';
    $handler->display->display_options['fields']['entity_id']['table'] = 'efq_efq_views_test';
    $handler->display->display_options['sorts'][$field]['id'] = $field;
    $handler->display->display_options['sorts'][$field]['field'] = $field;
    $handler->display->display_options['sorts'][$field]['table'] = 'efq_efq_views_test';
    $asc_sorted_view = clone $view;
    $view->execute();
    $this->assertIdentical(array_keys($this->getIds($view->result)), $this->map($expected_result));
    $view = clone $asc_sorted_view;
    $handler = $view->display['default']->handler;
    $handler->display->display_options['sorts'][$field]['order'] = 'DESC';
    $view->execute();
    $this->assertIdentical(array_keys($this->getIds($view->result)), $this->map(array_reverse($expected_result)));
  }

  /**
   * Tests view sort.
   */
  function testSort() {
    $this->runTestView('field_integer', array(1, 3, 0, 2));
    $this->runTestView('label', array(1, 3, 0, 2));
    $this->runTestView('entity_id', array(0, 1, 2, 3));
  }

}