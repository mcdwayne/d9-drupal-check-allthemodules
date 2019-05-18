<?php

namespace Drupal\Tests\commerce_migrate_csv_example\Kernel\Migrate;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests product attribute value migration.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate_csv_example
 */
class AttributeValueTest extends CsvTestBase {

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
    'commerce_migrate_csv_example',
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
  protected $fixtures = [__DIR__ . '/../../../fixtures/csv/example-products.csv'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_attribute');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->installConfig(['commerce_product']);
    $this->createAttribute(['Accessory Size', 'Color', 'Shoe Size', 'Size']);
    $this->executeMigration('csv_example_attribute_value');
  }

  /**
   * Test currency migration from Drupal 6 to 8.
   */
  public function testMigrateProductAttributeValueTest() {
    $this->assertProductAttributeValueEntity('1', 'color', 'Black', 'Black', '0');
    $this->assertProductAttributeValueEntity('2', 'size', 'XS', 'XS', '0');
    $this->assertProductAttributeValueEntity('3', 'size', 'SM', 'SM', '0');
    $this->assertProductAttributeValueEntity('4', 'size', 'MD', 'MD', '0');
    $this->assertProductAttributeValueEntity('5', 'size', 'LG', 'LG', '0');
    $this->assertProductAttributeValueEntity('6', 'size', 'XL', 'XL', '0');
    $this->assertProductAttributeValueEntity('7', 'size', '2XL', '2XL', '0');
    $this->assertProductAttributeValueEntity('8', 'size', '3XL', '3XL', '0');
    $this->assertProductAttributeValueEntity('9', 'color', 'Blue', 'Blue', '0');
    $this->assertProductAttributeValueEntity('10', 'size', '4', '4', '0');
    $this->assertProductAttributeValueEntity('11', 'color', 'Purple', 'Purple', '0');
    $this->assertProductAttributeValueEntity('12', 'size', '6', '6', '0');
    $this->assertProductAttributeValueEntity('13', 'size', '8', '8', '0');
    $this->assertProductAttributeValueEntity('14', 'size', '10', '10', '0');
  }

}
