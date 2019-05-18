<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product attribute migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductAttributeTest extends Ubercart6TestBase {

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
    $this->executeMigrations([
      'uc_attribute_field',
      'uc_product_attribute',
    ]);
  }

  /**
   * Test attribute migration.
   */
  public function testMigrateProductAttributeTest() {
    $this->assertProductAttributeEntity('design', 'Cool Designs for your towel', 'radios');
    $this->assertProductAttributeEntity('color', 'Color', 'checkbox');
    // Tests that the attribute name longer than 32 characters is truncated.
    $this->assertProductAttributeEntity('model_size_attribute', 'Model size', 'select');
    $this->assertProductAttributeEntity('name', 'Name', 'text');
  }

}
