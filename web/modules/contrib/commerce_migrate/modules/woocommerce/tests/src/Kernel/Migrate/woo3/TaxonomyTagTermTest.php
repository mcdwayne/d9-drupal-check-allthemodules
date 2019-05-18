<?php

namespace Drupal\Tests\commerce_migrate_woocommerce\Kernel\Migrate\woo3;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests migration of tag terms.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_woocommerce
 */
class TaxonomyTagTermTest extends CsvTestBase {

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
    $this->executeMigrations(['woo3_taxonomy_vocabulary', 'woo3_tag_term']);
  }

  /**
   * Tests the WooCommerce taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerm() {
    $this->assertTermEntity(1, 'Fleece', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(2, 'Organic cotton', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(3, 'Punk', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(4, 'Classical', 'tags', '', NULL, 0, []);
  }

}
