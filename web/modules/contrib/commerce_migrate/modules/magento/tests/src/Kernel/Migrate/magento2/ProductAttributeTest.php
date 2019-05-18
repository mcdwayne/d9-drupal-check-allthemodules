<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests product attribute migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class ProductAttributeTest extends CsvTestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
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
    $this->executeMigration('magento2_product_attribute');
  }

  /**
   * Test attribute migration.
   */
  public function testMigrateProductAttributeTest() {
    $this->assertProductAttributeEntity('activity', 'Activity', 'select');
    $this->assertProductAttributeEntity('eco_collection', 'Eco collection', 'select');
    $this->assertProductAttributeEntity('performance_fabric', 'Performance fabric', 'select');
    $this->assertProductAttributeEntity('color', 'Color', 'select');
    $this->assertProductAttributeEntity('size', 'Size', 'select');
  }

}
