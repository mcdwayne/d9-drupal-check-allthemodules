<?php

/**
 * @file
 * Tests for EntityFieldQuery Views query features.
 */

namespace Drupal\efq_views\Tests;

/**
 * Class EntityFieldQueryViewsNodeLinkTest
 *
 * @package Drupal\efq_views\Tests
 */
class EntityFieldQueryViewsNodeLinkTest extends EntityFieldQueryViewsTestBase {
  static function getInfo() {
    return array(
      'name' => 'EntityFieldQuery Views node link',
      'description' => 'Tests EntityFieldQuery Views sees a Views-defined special field.',
      'group' => 'EntityFieldQuery Views',
    );
  }

  function view() {
    $view = new view();
    $view->base_table = 'efq_node';
    /* Display: Master */
    $handler = $view->new_display('default');
    /* Field: Node: Edit link */
    $handler->display->display_options['fields']['edit_node']['id'] = 'edit_node';
    $handler->display->display_options['fields']['edit_node']['table'] = 'views_entity_node';
    $handler->display->display_options['fields']['edit_node']['field'] = 'edit_node';
    return $view;
  }

  function testNodeLink() {
    $node = $this->drupalCreateNode();
    $account = $this->drupalCreateUser(array('bypass node access'));
    $this->drupalLogin($account);
    $this->runView();
    $this->assertRaw('Edit link:');
    $this->clickLink('edit');
    $this->assertFieldByName('title', $node->title);
  }
}
