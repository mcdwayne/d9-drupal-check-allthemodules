<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product type migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductTypeTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->installConfig(['commerce_product']);
    $migration = $this->getMigration('uc6_product_type');
    $this->executeMigration($migration);

    // Rerun the migration.
    $table_name = $migration->getIdMap()->mapTableName();
    $default_connection = \Drupal::database();
    $default_connection->truncate($table_name)->execute();
    $this->executeMigration($migration);
  }

  /**
   * Test product type migration.
   */
  public function testProductType() {
    $description = 'A type of spacecraft capable of traveling to the solar systems of other stars';
    $this->assertProductTypeEntity('ship', 'Ship', $description, 'default');
    $description = 'This node displays the representation of a product for sale on the website. It includes all the unique information that can be attributed to a specific model number.';
    $this->assertProductTypeEntity('product', 'Product', $description, 'default');
  }

}
