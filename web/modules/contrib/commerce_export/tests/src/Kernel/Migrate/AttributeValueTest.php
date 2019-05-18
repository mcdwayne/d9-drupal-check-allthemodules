<?php

namespace Drupal\Tests\commerce_export\Kernel\Migrate;

/**
 * Tests product attribute value migration.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_export
 */
class AttributeValueTest extends TestBase {

  use MigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'path',
    'commerce_product',
  ];

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
    $this->createAttribute();
  }

  /**
   * Test currency migration from Drupal 6 to 8.
   */
  public function testMigrateProductAttributeValueTest() {
    $this->enableModules(['commerce_export']);
    $this->executeMigration('import_attribute_value');

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
