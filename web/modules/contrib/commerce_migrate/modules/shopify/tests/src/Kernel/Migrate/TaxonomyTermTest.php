<?php

namespace Drupal\Tests\commerce_migrate_shopify\Kernel\Migrate;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests migration of tag terms.
 *
 * @requires module migrate_plus
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_shopify
 */
class TaxonomyTermTest extends CsvTestBase {

  use CommerceMigrateCoreTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate',
    'commerce_migrate_shopify',
    'migrate_plus',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected $fixtures = __DIR__ . '/../../../fixtures/csv/shopify-products_export_test.csv';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('taxonomy_term');
    $this->executeMigrations(['shopify_taxonomy_vocabulary', 'shopify_taxonomy_term']);
  }

  /**
   * Tests the shopify taxonomy term to Drupal 8 migration.
   */
  public function testTaxonomyTerm() {
    $this->assertTermEntity(1, '1 Size', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(2, 'Accessory', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(3, 'Bag', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(4, 'Multi', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(5, 'sure', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(6, 'suredesign', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(7, 'suretshirts', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(8, 'Thai Hmong Embroidered Bag', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(9, 'Embroidered Ohm | Ganesha Print', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(10, 'Green', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(11, 'Yellow', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(12, 'closeout', 'tags', '', NULL, 0, []);
    $this->assertTermEntity(13, 'sales', 'tags', '', NULL, 0, []);
  }

}
