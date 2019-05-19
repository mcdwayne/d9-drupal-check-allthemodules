<?php

namespace Drupal\Tests\views_collapsible_list\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for certain page additions.
 *
 * @group views_collapsible_list
 */
class ResourcesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views_collapsible_list',
    'views_collapsible_list_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin = $this->createUser();
    $this->drupalLogin($admin);

    // Need at least one piece of content for the style plugin to get used.
    Node::create([
      'type' => 'article',
      'title' => 'Test',
    ])->save();
  }

  /**
   * Tests that the fields to collapse were included.
   */
  public function testFieldsAttached() {
    $this->drupalGet('views-collapsible-list-test');
    $settings = $this->getDrupalSettings();
    $this->assertArrayHasKey('viewsCollapsibleList', $settings, 'The list of fields to collapse was not attached.');
  }

}
