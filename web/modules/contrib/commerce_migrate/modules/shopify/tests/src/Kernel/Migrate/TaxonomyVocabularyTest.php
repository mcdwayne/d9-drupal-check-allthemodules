<?php

namespace Drupal\Tests\commerce_migrate_shopify\Kernel\Migrate;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateCoreTestTrait;
use Drupal\taxonomy\VocabularyInterface;
use Drupal\Tests\commerce_migrate\Kernel\CsvTestBase;

/**
 * Tests migration of vocabularies.
 *
 * @requires module migrate_source_csv
 *
 * @group commerce_migrate
 * @group commerce_migrate_shopify
 */
class TaxonomyVocabularyTest extends CsvTestBase {

  use CommerceMigrateCoreTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'taxonomy',
    'commerce_migrate_shopify',
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
    $this->executeMigration('shopify_taxonomy_vocabulary');
  }

  /**
   * Tests Shopify vocabulary migration.
   */
  public function testTaxonomyVocabulary() {
    $this->assertVocabularyEntity('tags', 'Tags', 'Tags', VocabularyInterface::HIERARCHY_DISABLED, 0);
  }

}
