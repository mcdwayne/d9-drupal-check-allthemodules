<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests commerce field instance migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProductAttributeTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'datetime',
    'file',
    'image',
    'link',
    'migrate_plus',
    'node',
    'path',
    'profile',
    'system',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('profile');
    $this->executeMigrations([
      'commerce1_product_variation_type',
      'commerce1_product_type',
      'd7_field',
      'commerce1_product_attribute',
    ]);
  }

  /**
   * Test attribute migration from Commerce 1.
   */
  public function testMigrateProductAttributeTest() {
    $this->assertProductAttributeEntity('bag_size', 'Bag size', 'select');
    $this->assertProductAttributeEntity('color', 'Color', 'select');
    $this->assertProductAttributeEntity('hat_size', 'Hat size', 'select');
    $this->assertProductAttributeEntity('shoe_size', 'Shoe size', 'select');
    // Tests that the attribute name longer than 32 characters is truncated.
    $this->assertProductAttributeEntity('storage_capacity_with_very_lo', 'Storage capacity', 'select');
    $this->assertProductAttributeEntity('top_size', 'Top size', 'select');
  }

}
