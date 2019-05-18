<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product type migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ProductTypeTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_migrate_ubercart',
    'commerce_price',
    'commerce_store',
    'node',
    'path',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
    $this->executeMigration('uc7_product_type');
  }

  /**
   * Test product type migration.
   */
  public function testProductType() {
    $description = 'Use <em>products</em> to represent items for sale on the website, including all the unique information that can be attributed to a specific model number.';
    $this->assertProductTypeEntity('product', 'Product', $description, 'default');
    $description = 'Fun!';
    $this->assertProductTypeEntity('entertainment', 'Entertainment', $description, 'default');
  }

}
