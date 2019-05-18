<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests Product migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class ProductVariationTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'action',
    'address',
    'commerce',
    'commerce',
    'commerce_migrate',
    'commerce_migrate_magento',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'entity',
    'field',
    'inline_entity_form',
    'options',
    'path',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = __DIR__ . '/../../../../fixtures/csv/magento2-catalog_product_20180326_013553_test.csv';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    // Make sure uid 1 is created.
    user_install();

    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig('commerce_product');
    $this->executeMigrations([
      'magento2_product_variation_type',
      'magento2_product_variation',
    ]);
  }

  /**
   * Test product variation migration.
   */
  public function testProductVariation() {
    $this->assertProductVariationEntity(1, 'bag', '1', '24-MB01', '34.000000', 'USD', NULL, 'Joust Duffle Bag', 'default', '1521962400', '1521962400');
    $this->assertProductVariationEntity(2, 'bag', '1', '24-MB02', '59.000000', 'USD', NULL, 'Fusion Backpack', 'default', '1521962400', '1521962400');
    $this->assertProductVariationEntity(3, 'bag', '1', '24-UB02', '74.000000', 'USD', NULL, 'Impulse Duffle', 'default', '1521962400', '1521962400');
    $this->assertProductVariationEntity(4, 'bag', '1', '24-WB01', '32.000000', 'USD', NULL, 'Voyage Yoga Bag', 'default', '1521962400', '1521962400');
    $this->assertProductVariationEntity(5, 'bag', '1', '24-WB02', '32.000000', 'USD', NULL, 'Compete Track Tote', 'default', '1521962400', '1521962400');
    // Test a product with a fractional price.
    $this->assertProductVariationEntity(31, 'bottom', '1', 'MSH02-32-Black', '32.500000', 'USD', NULL, 'Apollo Running Short-32-Black', 'default', '1521962520', '1521962520');

  }

}
