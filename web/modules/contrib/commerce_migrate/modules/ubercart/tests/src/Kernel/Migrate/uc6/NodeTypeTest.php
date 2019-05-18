<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\node\Entity\NodeType;

/**
 * Tests product node types are not migrated.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class NodeTypeTest extends Ubercart6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'migrate_plus',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['node']);
    $this->executeMigration('d6_node_type');
  }

  /**
   * Tests node type migration.
   */
  public function testNodeType() {
    $node_type = NodeType::load('page');
    $this->assertInstanceOf(NodeType::class, $node_type);
    $node_type = NodeType::load('product');
    $this->assertNULL($node_type);
    $node_type = NodeType::load('ship');
    $this->assertNULL($node_type);
  }

}
