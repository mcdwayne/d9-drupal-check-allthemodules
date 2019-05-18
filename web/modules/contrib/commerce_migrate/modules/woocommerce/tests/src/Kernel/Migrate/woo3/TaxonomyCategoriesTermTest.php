<?php

namespace Drupal\Tests\commerce_migrate_woocommerce\Kernel\Migrate\woo3;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests migrations of categories taxonomy terms.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_woocommerce
 */
class TaxonomyCategoriesTermTest extends CsvTestBase {

  use CommerceMigrateCoreTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate_woocommerce',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = __DIR__ . '/../../../../fixtures/csv/woo3-product-export-7-5-2018-1525686755964.csv';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations(['woo3_taxonomy_vocabulary', 'woo3_categories_term']);
  }

  /**
   * Tests the WooCommerce taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerm() {
    $this->assertTermEntity(1, 'Clothing', 'categories', '', NULL, 0, []);
    $this->assertTermEntity(2, 'T-shirts', 'categories', '', NULL, 0, [1]);
    $this->assertTermEntity(3, 'Hoodies', 'categories', '', NULL, 0, [1]);
    $this->assertTermEntity(4, 'Pocket', 'categories', '', NULL, 0, [3]);
    $this->assertTermEntity(5, 'Zip', 'categories', '', NULL, 0, [3]);
    $this->assertTermEntity(6, 'Posters', 'categories', '', NULL, 0, []);
    $this->assertTermEntity(7, 'Music', 'categories', '', NULL, 0, []);
    $this->assertTermEntity(8, 'Albums', 'categories', '', NULL, 0, [7]);
    $this->assertTermEntity(9, 'Singles', 'categories', '', NULL, 0, [7]);
  }

}
