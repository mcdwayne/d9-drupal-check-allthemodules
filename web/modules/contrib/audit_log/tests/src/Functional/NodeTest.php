<?php

namespace Drupal\Tests\audit_log\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests audit log functionality on node crud operations.
 *
 * @group audit_log
 */
class NodeTest extends NodeTestBase {

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node_test', 'audit_log', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser(['create article content']);
    $this->drupalLogin($web_user);
    $this->webUser = $web_user;
  }

  /**
   * Tests audit log functionality on node crud operations.
   */
  public function testNodeCrud() {
    $count = db_query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(0, $count);

    // Initial creation.
    $node = Node::create([
      'uid' => $this->webUser->id(),
      'type' => 'article',
      'title' => 'test_changes',
    ]);
    $node->save();

    $count = db_query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(1, $count);

    // Update the node without applying changes.
    $node->save();
    $count = db_query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(2, $count);

    // Apply changes.
    $node->title = 'updated';
    $node->save();

    $count = db_query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(3, $count);

    $node->delete();
    $count = db_query("SELECT COUNT(id) FROM {audit_log} WHERE entity_type = 'node'")->fetchField();
    $this->assertEquals(4, $count);
  }

}
