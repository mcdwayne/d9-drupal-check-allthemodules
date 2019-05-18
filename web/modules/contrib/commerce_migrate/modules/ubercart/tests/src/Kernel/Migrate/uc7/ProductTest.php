<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Node\Entity\Node;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests Product migration.
 *
 * @requires migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ProductTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'filter',
    'image',
    'menu_ui',
    'migrate_plus',
    'node',
    'path',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateProducts();
  }

  /**
   * Test product migration.
   */
  public function testProduct() {
    $this->assertProductEntity(1, 'product', '1', 'Breshtanti ale', TRUE, ['1'], ['1']);
    $this->assertProductVariationEntity(1, 'product', '1', 'drink-001', '50.000000', 'USD', '1', 'Breshtanti ale', 'default', '1493289860', NULL);

    $this->assertProductEntity(2, 'product', '1', 'Romulan ale', TRUE, ['1'], ['2']);
    $this->assertProductVariationEntity(2, 'product', '1', 'drink-002', '100.000000', 'USD', '2', 'Romulan ale', 'default', '1493326300', NULL);

    $this->assertProductEntity(3, 'entertainment', '1', 'Holosuite 1', TRUE, ['1'], ['3']);
    $this->assertProductVariationEntity(3, 'entertainment', '1', 'Holosuite-001', '40.000000', 'USD', '3', 'Holosuite 1', 'default', '1493326429', NULL);

    // There is only one node in the fixture that is not a product, node 4.
    $node = Node::load(4);
    $this->assertTrue($node, "Node 4 exists.");

    // Nodes 1, 2 and 3 should not exist.
    $nodes = [1, 2, 3];
    foreach ($nodes as $node) {
      $node = Node::load($node);
      $this->assertFalse($node, "Node $node exists.");
    }

  }

}
