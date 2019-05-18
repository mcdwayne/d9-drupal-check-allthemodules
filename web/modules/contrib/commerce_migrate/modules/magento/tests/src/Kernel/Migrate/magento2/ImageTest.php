<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Migrate images.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class ImageTest extends CsvTestBase {

  use CommerceMigrateCoreTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_migrate',
    'commerce_migrate_magento',
    'file',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = [
    __DIR__ . '/../../../../fixtures/csv/magento2-catalog_product_20180326_013553_test.csv',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->fs = \Drupal::service('file_system');
    $this->installEntitySchema('user');
    // Copy the source files.
    $this->fileMigrationSetup(__DIR__ . '/../../../../fixtures/images');
    $this->executeMigration('magento2_image');
  }

  /**
   * Tests image file migration from CSV source file.
   */
  public function testFileMigration() {
    // Test a base image.
    $this->assertFileEntity(1, 'mb01-blue-0.jpg', 'public://import/images/catalog/product/m/b/mb01-blue-0.jpg', 'image/jpeg', '246955', '1');
    // Test a small image.
    $this->assertFileEntity(2, 'msh02-black_main.jpg', 'public://import/images/catalog/product/m/s/msh02-black_main.jpg', 'image/jpeg', '45378', '1');
    // Test a thumbnail image.
    $this->assertFileEntity(3, 'wb01-black-0.jpg', 'public://import/images/catalog/product/w/b/wb01-black-0.jpg', 'image/jpeg', '112042', '1');
    // Test a swatch.
    $this->assertFileEntity(4, 'mb02-gray-0.jpg', 'public://import/images/catalog/product/m/b/mb02-gray-0.jpg', 'image/jpeg', '348654', '1');
    // Test an additional image.
    $this->assertFileEntity(5, 'mb02-blue-0.jpg', 'public://import/images/catalog/product/m/b/mb02-blue-0.jpg', 'image/jpeg', '408574', '1');
  }

}
