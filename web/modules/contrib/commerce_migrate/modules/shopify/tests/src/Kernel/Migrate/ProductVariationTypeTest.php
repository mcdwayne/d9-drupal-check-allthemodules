<?php

namespace Drupal\Tests\commerce_migrate_shopify\Kernel\Migrate\shopify;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests product variation type migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_shopify
 */
class ProductVariationTypeTest extends CsvTestBase {

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
    $this->executeMigration('shopify_product_variation_type');
  }

  /**
   * Test product variation migration.
   */
  public function testProductVariationType() {
    $this->assertProductVariationTypeEntity('bag_accessory', 'Bag Accessory', 'default', FALSE, []);
    $this->assertProductVariationTypeEntity('mens_short_sleeve_t_shirts', 'Mens Short Sleeve T-Shirts', 'default', FALSE, []);
    $this->assertProductVariationTypeEntity('mens_t_shirt', 'Mens T-Shirt', 'default', FALSE, []);
  }

}
