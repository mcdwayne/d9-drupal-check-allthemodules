<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Migrate category.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class TaxonomyTermTest extends CsvTestBase {

  use CommerceMigrateCoreTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_migrate',
    'commerce_migrate_magento',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * The cached taxonomy tree items, keyed by vid and tid.
   *
   * @var array
   */
  protected $treeData = [];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = __DIR__ . '/../../../../fixtures/csv/magento2-catalog_product_20180326_013553.csv';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigration('magento2_category');
    $this->executeMigration('magento2_category_term');
  }

  /**
   * Tests the Drupal 7 taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerm() {
    $this->assertTermEntity(1, 'Gear', 'default_category', '', NULL, 0, []);
    $this->assertTermEntity(2, 'Bags', 'default_category', '', NULL, 0, [1]);
    $this->assertTermEntity(3, 'Collections', 'default_category', '', NULL, 0, []);
    $this->assertTermEntity(4, 'New Luma Yoga Collection', 'default_category', '', NULL, 0, [3]);
    $this->assertTermEntity(8, 'Video Download', 'default_category', '', NULL, 0, [7]);
  }

}
