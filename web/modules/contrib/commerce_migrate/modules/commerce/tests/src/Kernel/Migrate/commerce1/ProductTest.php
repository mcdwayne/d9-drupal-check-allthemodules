<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProductTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateProducts();
  }

  /**
   * Test product migration from Drupal 7 to 8.
   */
  public function testProduct() {
    $this->assertProductEntity(
      15,
      'bags_cases',
      '1',
      'Go green with Drupal Commerce Reusable Tote Bag',
      TRUE,
      ['1'],
      ['1']
    );

    // Tests a product with multiple variations.
    $this->assertProductEntity(
      26,
      'storage_devices',
      '1',
      'Commerce Guys USB Key',
      TRUE,
      ['1'],
      [
        '28',
        '29',
        '30',
      ]
    );
  }

}
