<?php

namespace Drupal\Tests\commerce_migrate_shopify\Kernel\Migrate\shopify;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests product variation migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_shopify
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
    'commerce_migrate_shopify',
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
  protected $fixtures = __DIR__ . '/../../../fixtures/csv/shopify-products_export_test.csv';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    // Make sure uid 1 is created.
    user_install();

    $this->installConfig('commerce_product');
    $this->installEntitySchema('commerce_store');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->executeMigrations([
      'shopify_product_variation_type',
      'shopify_product_variation',
    ]);
  }

  /**
   * Test product variation migration.
   */
  public function testProductVariation() {
    $this->assertProductVariationEntity(1, 'bag_accessory', '1', 'THEB15--1-Size', '30.000000', 'USD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntity(2, 'bag_accessory', '1', 'YMB01-Green--1-Size', '18.950000', 'USD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntity(3, 'bag_accessory', '1', 'YMB01-Yellow--1-Size', '18.950000', 'USD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntity(4, 'mens_short_sleeve_t_shirts', '1', 'YGS08-White--1-Size', '12.000000', 'USD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntity(5, 'mens_t_shirt', '1', 'MT01-Brick--M', '18.000000', 'USD', NULL, '', 'default', NULL, NULL);
    $this->assertProductVariationEntity(12, 'mens_t_shirt', '1', 'MT01-Gray--XXL', '18.000000', 'USD', NULL, '', 'default', NULL, NULL);
  }

}
