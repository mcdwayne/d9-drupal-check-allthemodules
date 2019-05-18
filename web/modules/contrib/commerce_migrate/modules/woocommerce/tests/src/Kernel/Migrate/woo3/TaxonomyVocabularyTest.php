<?php

namespace Drupal\Tests\commerce_migrate_woocommerce\Kernel\Migrate\woo3;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests migration of vocabularies.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_woocommerce
 */
class TaxonomyVocabularyTest extends CsvTestBase {

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
    $this->executeMigration('woo3_taxonomy_vocabulary');
  }

  /**
   * Tests the Drupal 7 taxonomy vocabularies to Drupal 8 migration.
   */
  public function testTaxonomyVocabulary() {
    $this->assertVocabularyEntity('tags', 'Tags', 'Tags', VocabularyInterface::HIERARCHY_DISABLED, 0);
    $this->assertVocabularyEntity('categories', 'Categories', 'Product categories', VocabularyInterface::HIERARCHY_DISABLED, 0);
  }

}
