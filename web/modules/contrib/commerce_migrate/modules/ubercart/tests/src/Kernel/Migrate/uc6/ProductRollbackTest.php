<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;

/**
 * Tests rollback of Product migration.
 *
 * @requires migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductRollbackTest extends ProductTest {

  /**
   * Test product migration rollback.
   */
  public function testProduct() {
    $this->executeRollback('d6_node_translation:product');
    $this->executeRollback('d6_node:product');

    $product_ids = [1, 2, 3, 6, 7, 8];
    foreach ($product_ids as $product_id) {
      $product = Product::load($product_id);
      $this->assertFalse($product, "Product $product_id exists.");
    }
    $product_ids = [4, 5];
    foreach ($product_ids as $product_id) {
      $product = Product::load($product_id);
      $this->assertTrue($product, "Product $product_id does not exist.");
    }

    $product_variation_ids = [1, 2, 3, 4, 5];
    foreach ($product_variation_ids as $product_variation_id) {
      $product_variation = ProductVariation::load($product_variation_id);
      $this->assertTrue($product_variation, "Product variation $product_variation_id does not exist.");
    }
  }

}
