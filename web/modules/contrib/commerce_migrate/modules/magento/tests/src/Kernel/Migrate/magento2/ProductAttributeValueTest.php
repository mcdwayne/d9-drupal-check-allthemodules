<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests product attribute value migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class ProductAttributeValueTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce',
    'commerce_migrate',
    'commerce_migrate_magento',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = __DIR__ . '/../../../../fixtures/csv/magento2-catalog_product_20180326_013553.csv';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->executeMigrations([
      'magento2_product_attribute',
      'magento2_product_attribute_value',
    ]);
  }

  /**
   * Tests product attribute value migration.
   */
  public function testProductAttributeValue() {
    $this->assertProductAttributeValueEntity('1', 'activity', 'Gym', 'Gym', '0');
    $this->assertProductAttributeValueEntity('2', 'activity', 'Hiking', 'Hiking', '0');
    $this->assertProductAttributeValueEntity('3', 'activity', 'Trail', 'Trail', '0');
    $this->assertProductAttributeValueEntity('4', 'activity', 'Urban', 'Urban', '0');
    $this->assertProductAttributeValueEntity('5', 'erin_recommends', 'Yes', 'Yes', '0');
    $this->assertProductAttributeValueEntity('6', 'features_bags', 'Audio Pocket', 'Audio Pocket', '0');
    $this->assertProductAttributeValueEntity('7', 'features_bags', 'Waterproof', 'Waterproof', '0');
    $this->assertProductAttributeValueEntity('8', 'features_bags', 'Lightweight', 'Lightweight', '0');
    $this->assertProductAttributeValueEntity('9', 'features_bags', 'Laptop Sleeve', 'Laptop Sleeve', '0');

    // Test that all the attribute options are available for an attribute.
    $attribute = ProductAttribute::load('material');
    $expected_attributes =
      [
        'Burlap',
        'Canvas',
        'Cocona&reg; performance fabric',
        'CoolTech&trade;',
        'Cotton',
        'EverCool&trade;',
        'Fleece',
        'Foam',
        'HeatTec&reg;',
        'Hemp',
        'Jersey',
        'Leather',
        'Linen',
        'LumaTech&trade;',
        'Lycra&reg;',
        'Mesh',
        'Metal',
        'Microfiber',
        'Nylon',
        'Organic Cotton',
        'Plastic',
        'Polyester',
        'Rayon',
        'Rubber',
        'Silicone',
        'Spandex',
        'Stainless Steel',
        'Suede',
        'Synthetic',
        'TENCEL',
        'Wool',
      ];

    $actual_attributes = [];
    /** @var \Drupal\commerce_product\Entity\ProductAttributeValue $attributeValue */
    foreach ($attribute->getValues() as $attributeValue) {
      $actual_attributes[] = $attributeValue->getName();
    }
    asort($actual_attributes);
    $this->assertSame($expected_attributes, $actual_attributes);
  }

}
