<?php

/**
 * @file
 * Contains \Drupal\block_page\Tests\BlockPageNodeSelectionTest.
 */

namespace Drupal\block_page\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests selection block pages based on nodes.
 */
class BlockPageNodeSelectionTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('block_page', 'node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Block Page node selection test',
      'description' => 'Tests selection block pages based on nodes.',
      'group' => 'Block Page',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Page'));
    $this->drupalLogin($this->drupalCreateUser(array('administer block pages', 'create article content', 'create page content')));
  }

  /**
   * Tests that a node bundle condition controls the node view page.
   */
  public function testAdmin() {
    // Create two nodes, and view their pages.
    $node1 = $this->drupalCreateNode(array('type' => 'page'));
    $node2 = $this->drupalCreateNode(array('type' => 'article'));
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(200);
    $this->assertText($node1->label());
    $this->drupalGet('node/' . $node2->id());
    $this->assertResponse(200);
    $this->assertText($node2->label());

    // Create a new block page to take over node pages.
    $edit = array(
      'label' => 'Node View',
      'id' => 'node_view',
      'path' => 'node/%',
    );
    $this->drupalPostForm('admin/structure/block_page/add', $edit, 'Save');
    // Their pages should now use the default 404 page variant.
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(404);
    $this->assertNoText($node1->label());
    $this->drupalGet('node/' . $node2->id());
    $this->assertResponse(404);
    $this->assertNoText($node2->label());

    // Add a new page variant.
    $this->drupalGet('admin/structure/block_page/manage/node_view');
    $this->clickLink('Add new page variant');
    $this->clickLink('Landing page');
    $edit = array(
      'page_variant[label]' => 'First',
    );
    $this->drupalPostForm(NULL, $edit, 'Add page variant');

    // Add a node bundle condition for articles.
    $this->clickLink('Add new selection condition');
    $this->clickLink('Node Bundle');
    $edit = array(
      'condition[bundles][article]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, 'Add selection condition');

    // The page node will 404, but the article node will display the page variant.
    $this->drupalGet('node/' . $node1->id());
    $this->assertResponse(404);
    $this->assertNoText($node1->label());
    $this->drupalGet('node/' . $node2->id());
    $this->assertResponse(200);
    $this->assertText('Node View');
  }

}
