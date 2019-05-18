<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\commerce_product\Entity\Product;
use Drupal\node\Entity\Node;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests Product migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_ubercart6
 */
class NodeTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'content_translation',
    'language',
    'menu_ui',
    'migrate_plus',
    'path',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateProductVariations();
  }

  /**
   * Test product migration.
   */
  public function testProduct() {
    // Checks that the Ubercart product node id are not migrated.
    $node = Node::load(1);
    $this->assertNull($node);
    $node = Node::load(2);
    $this->assertNull($node);
    $node = Node::load(3);
    $this->assertNull($node);
    $node = Node::load(4);
    $this->assertNull($node);
    $node = Node::load(5);
    $this->assertNull($node);

    // Assert the page node is migrated as a node.
    $node = Node::load(6);
    $this->assertInstanceOf(Node::class, $node);

    // Assert the products.
    $this->assertProductEntity(1, 'product', '1', 'Bath Towel', TRUE, ['1'], ['1']);
    $this->assertProductVariationEntity(1, 'product', '1', 'towel-bath-001', '20.000000', 'NZD', '1', 'Bath Towel', 'default', '1492867780', NULL);

    $this->assertProductEntity(2, 'product', '1', 'Beach Towel', TRUE, ['1'], ['2']);
    $this->assertProductVariationEntity(2, 'product', '1', 'towel-beach-001', '15.000000', 'NZD', '2', 'Beach Towel', 'default', '1492989418', NULL);

    $this->assertProductEntity(3, 'product', '1', 'Fairy cake', TRUE, ['1'], ['3']);
    $this->assertProductVariationEntity(3, 'product', '1', 'Fairy-Cake-001', '1500.000000', 'NZD', '3', 'Fairy cake', 'default', '1492989703', NULL);

    $this->assertProductEntity(4, 'ship', '1', 'Golgafrincham B-Ark', TRUE, ['1'], ['4']);
    $this->assertProductVariationEntity(4, 'ship', '1', 'ship-001', '6000000000.000000', 'NZD', '4', 'Golgafrincham B-Ark', 'default', '1500868190', NULL);

    $this->assertProductEntity(5, 'ship', '1', 'Heart of Gold', TRUE, ['1'], ['5']);
    $this->assertProductVariationEntity(5, 'ship', '1', 'ship-002', '123000000.000000', 'NZD', '5', 'Heart of Gold', 'default', '1500868361', NULL);

    // Checks that the products are not duplicated. This can happen if the node
    // revision migration is executed for a product node.
    $product = Product::load(6);
    $this->assertNull($product);
    $product = Product::load(7);
    $this->assertNull($product);
  }

}
