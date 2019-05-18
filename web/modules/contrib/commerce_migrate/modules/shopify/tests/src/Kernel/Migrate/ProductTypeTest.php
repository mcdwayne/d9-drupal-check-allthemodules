<?php

namespace Drupal\Tests\commerce_migrate_shopify\Kernel\Migrate;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests product type migration.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_shopify
 */
class ProductTypeTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * Filename of the test fixture.
   *
   * @var string
   */
  protected $fixtures = __DIR__ . '/../../../fixtures/csv/shopify-products_export_test.csv';

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
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
    $this->executeMigration('shopify_product_type');
  }

  /**
   * Test product type migration.
   */
  public function testProductType() {
    $this->assertProductTypeEntity('bag_accessory', 'Bag Accessory', NULL, 'default');
    $this->assertProductTypeEntity('mens_short_sleeve_t_shirts', 'Mens Short Sleeve T-Shirts', NULL, 'default');
    $this->assertProductTypeEntity('mens_t_shirt', 'Mens T-Shirt', NULL, 'default');
  }

}
