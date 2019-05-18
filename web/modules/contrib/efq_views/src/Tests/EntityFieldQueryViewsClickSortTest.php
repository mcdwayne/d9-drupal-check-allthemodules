<?php

/**
 * @file
 * Tests for EntityFieldQuery Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsClickSortTest
 *
 * @package Drupal\efq_views\Tests
 */
class EntityFieldQueryViewsClickSortTest extends EntityFieldQueryViewsSortTestBase {

  static function getInfo() {
    return array(
      'name' => 'EntityFieldQuery click sort',
      'description' => 'Tests EntityFieldQuery click sorts.',
      'group' => 'EntityFieldQuery Views',
    );
  }

  function testClickSort() {
    $view = new view();
    $view->base_table = 'efq_efq_views_test';
    $handler = $view->new_display('default');
    $handler->display->display_options['row_options']['default_field_elements'] = FALSE;
    $handler->display->display_options['fields']['field_integer']['id'] = 'field_integer';
    $handler->display->display_options['fields']['field_integer']['field'] = 'field_integer';
    $handler->display->display_options['fields']['field_integer']['table'] = 'efq_efq_views_test';
    $handler->display->display_options['style_plugin'] = 'table';
    $handler->display->display_options['style_options']['columns'] = array(
      'field_integer' => 'field_integer',
    );
    $handler->display->display_options['style_options']['info'] = array(
      'field_integer' => array(
        'sortable' => 1,
        'default_sort_order' => 'asc',
        'align' => '',
        'separator' => '',
        'empty_column' => 0,
      ),
    );
    $view->execute();
    $view->field['field_integer']->click_sort('asc');
    $view->execute();
  }

}
