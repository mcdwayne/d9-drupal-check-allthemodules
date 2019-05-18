<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product attribute value migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ProductAttributeValueTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'path',
    'text',
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
    $this->assertProductAttributeValueEntity('1', 'size', 'Small', 'Small', '0');
    $this->assertProductAttributeValueEntity('2', 'size', 'Medium', 'Medium', '0');
    $this->assertProductAttributeValueEntity('3', 'size', 'Large', 'Large', '0');
    $this->assertProductAttributeValueEntity('4', 'extra', 'Frosted glass', 'Frosted glass', '0');
    $this->assertProductAttributeValueEntity('5', 'extra', 'Ice', 'Ice', '0');
    $this->assertProductAttributeValueEntity('6', 'extra', 'Lemon', 'Lemon', '0');
  }

}
