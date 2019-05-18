<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Migrate\magento2;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Migrate category.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class TaxonomyVocabularyTest extends CsvTestBase {

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
  }

  /**
   * Tests Magento 2 category to Drupal 8 vocabulary migration.
   */
  public function testTaxonomyVocabulary() {
    $this->assertVocabularyEntity('default_category', 'Default Category', NULL, VocabularyInterface::HIERARCHY_DISABLED, 0);
  }

}
