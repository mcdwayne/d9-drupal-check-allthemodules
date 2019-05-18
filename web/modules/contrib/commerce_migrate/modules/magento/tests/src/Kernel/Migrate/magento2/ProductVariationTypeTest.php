<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests product type migration.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento
 */
class ProductVariationTypeTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $fixtures = __DIR__ . '/../../../../fixtures/csv/magento2-catalog_product_20180326_013553_test.csv';

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
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
    $this->executeMigration('magento2_product_variation_type');
  }

  /**
   * Test product type migration.
   */
  public function testProductVariationType() {
    $this->assertProductVariationTypeEntity('bag', 'Bag', 'default', FALSE, []);
    $this->assertProductVariationTypeEntity('gear', 'Gear', 'default', FALSE, []);
    $this->assertProductVariationTypeEntity('sprite_stasis_ball', 'Sprite Stasis Ball', 'default', FALSE, []);
  }

}
