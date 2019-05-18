<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\field\Entity\FieldConfig;

/**
 * Migrate field instance tests.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class FieldInstanceTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'image',
    'migrate_plus',
    'node',
    'path',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
  }

  /**
   * Tests migration of Ubercart 7 field instances.
   */
  public function testFieldInstanceMigration() {
    $fields = [
      'commerce_product.product.field_number',
      'commerce_product.product.field_sustainability',
      'commerce_product.product.taxonomy_catalog',
      'commerce_product.product.uc_product_image',
      'node.article.field_image',
      'node.article.field_tags',
      'node.page.field_number',
      'taxonomy_term.catalog.uc_catalog_image',
    ];
    foreach ($fields as $field) {
      /** @var \Drupal\field\Entity\FieldStorageConfig $storage */
      $field_config = FieldConfig::load($field);
      $this->assertInstanceOf(FieldConfig::class, $field_config, "$field is not an instance of FieldConfig");
    }
  }

}
