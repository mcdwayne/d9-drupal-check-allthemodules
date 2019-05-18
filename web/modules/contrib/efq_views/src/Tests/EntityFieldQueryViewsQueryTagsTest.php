<?php

/**
 * @file
 * Tests for EntityFieldQuery Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsQueryTagsTest
 *
 * @package Drupal\efq_views\Tests
 */
class EntityFieldQueryViewsQueryTagsTest extends EntityFieldQueryViewsTestBase {
  static function getInfo() {
    return array(
      'name' => 'EntityFieldQuery Views query tags',
      'description' => 'Tests adding tags to EntityFieldQuery Views queries.',
      'group' => 'EntityFieldQuery Views',
    );
  }

  function view() {
    $view = parent::view();
    $handler = $view->display['default'];
    $handler->display_options['query']['options']['query_tags'] = array('test_query_tag');
    return $view;
  }

  function testQueryTags() {
    global $efq_test_query;
    $this->runView();
    // The query is present in the global variable when the query tag
    // was inserted correctly.
    // @see efq_views_test_query_test_query_tag_alter().
    $this->assertNotNull($efq_test_query, 'The query must be present in the global variable');
  }

}
