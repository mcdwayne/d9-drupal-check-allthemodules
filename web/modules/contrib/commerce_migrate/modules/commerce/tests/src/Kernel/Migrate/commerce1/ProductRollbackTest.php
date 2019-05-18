<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;

/**
 * Tests rollback of Product migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProductRollbackTest extends ProductTest {

  /**
   * Test product migration rollback.
   */
  public function testProduct() {
    $this->executeRollback('commerce1_product');

    $product_ids = [15, 16];
    foreach ($product_ids as $product_id) {
      $product = Product::load($product_id);
      $this->assertFalse($product, "Product $product_id exists.");
    }

    $product_variation_ids = [1, 28, 29, 30];
    foreach ($product_variation_ids as $product_variation_id) {
      $product_variation = ProductVariation::load($product_variation_id);
      $this->assertTrue($product_variation, "Product variation $product_variation_id does not exist.");
    }
  }

}
