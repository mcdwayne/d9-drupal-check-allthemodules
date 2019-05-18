<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product attribute migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ProductAttributeTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;


  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_price', 'commerce_product'];

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
    $this->assertProductAttributeEntity('size', 'Size', 'select');
    $this->assertProductAttributeEntity('extra', 'Extra', 'checkbox');
    // Tests that the attribute name longer than 32 characters is truncated.
    $this->assertProductAttributeEntity('attribute_with_name_', 'Long name test', 'radios');
    $this->assertProductAttributeEntity('duration', 'Duration', 'text');
  }

}
