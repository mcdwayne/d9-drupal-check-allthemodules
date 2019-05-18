<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Node\Entity\Node;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\commerce_product\Entity\Product;

/**
 * Tests Product migration.
 *
 * @requires migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductTest extends Ubercart6TestBase {

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
    $this->migrateProducts();
  }

  /**
   * Test product migration.
   */
  public function testProduct() {
    $this->assertProductEntity(1, 'product', '1', 'Bath Towel', TRUE, ['1'], ['1']);
    $this->assertProductVariationEntity(1, 'product', '1', 'towel-bath-001', '20.000000', 'NZD', '1', 'Bath Towel', 'default', '1492867780', NULL);

    $this->assertProductEntity(2, 'product', '1', 'Beach Towel', TRUE, ['1'], ['2']);
    $this->assertProductVariationEntity(2, 'product', '1', 'towel-beach-001', '15.000000', 'NZD', '2', 'Beach Towel', 'default', '1492989418', NULL);

    $this->assertProductEntity(3, 'product', '1', 'Fairy cake', TRUE, ['1'], ['3']);
    $this->assertProductVariationEntity(3, 'product', '1', 'Fairy-Cake-001', '1500.000000', 'NZD', '3', 'Fairy cake', 'default', '1492989703', NULL);

    // There is only one node in the fixture that is not a product, node 6.
    $node = Node::load(6);
    $this->assertTrue($node, "Node 6 exists.");

    // Nodes 1 to 5 and node 7 and 8 should not exist.
    $nodes = [1, 2, 3, 4, 5, 7, 8];
    foreach ($nodes as $node) {
      $node = Node::load($node);
      $this->assertFalse($node, "Node $node exists.");
    }

    // Test that translations are working.
    $product = Product::load(1);
    $this->assertSame('en', $product->langcode->value);
    $this->assertTrue($product->hasTranslation('es'), "Product 1 missing the Spanish translation");
    $product = Product::load(2);
    $this->assertSame('und', $product->langcode->value);
    $this->assertFalse($product->hasTranslation('es'), "Product 2 should not have a Spanish translation");
    $product = Product::load(3);
    $this->assertSame('en', $product->langcode->value);
    $this->assertTrue($product->hasTranslation('es'), "Product 3 missing the Spanish translation");

    // Test that content_translation_source is set.
    $manager = $this->container->get('content_translation.manager');
    $this->assertSame('en', $manager->getTranslationMetadata($product->getTranslation('es'))
      ->getSource());
  }

}
