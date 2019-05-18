<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product attribute value migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductAttributeValueTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_attribute_value');
    $this->executeMigrations([
      'uc_attribute_field',
      'uc_product_attribute',
      'uc_product_attribute_value',
    ]);
  }

  /**
   * Test product attribute value migration.
   */
  public function testProductAttributeValue() {
    $this->assertProductAttributeValueEntity('1', 'design', 'Heart of Gold', 'Heart of Gold', '0');
    $this->assertProductAttributeValueEntity('2', 'design', 'Trillian', 'Trillian', '0');
    $this->assertProductAttributeValueEntity('3', 'design', 'Pan Galactic Gargle Blaster', 'Pan Galactic Gargle Blaster', '0');
    $this->assertProductAttributeValueEntity('4', 'color', 'White', 'White', '500');
    $this->assertProductAttributeValueEntity('5', 'color', 'Gold', 'Gold', '500');
    $this->assertProductAttributeValueEntity('6', 'model_size_attribute', 'Keychain', 'Keychain', '20');
    $this->assertProductAttributeValueEntity('7', 'model_size_attribute', 'Desk', 'Desk', '400');
  }

}
