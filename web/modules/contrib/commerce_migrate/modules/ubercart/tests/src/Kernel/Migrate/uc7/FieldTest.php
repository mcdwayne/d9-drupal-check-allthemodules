<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests migration of Ubercart 7 fields.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class FieldTest extends Ubercart7TestBase {

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
    $this->installEntitySchema('commerce_product');
    $this->executeMigration('d7_field');
  }

  /**
   * Tests the Ubercart 7 field to Drupal 8 migration.
   */
  public function testFields() {
    $field_storages = [
      'commerce_product.field_number',
      'commerce_product.field_sustainability',
      'commerce_product.taxonomy_catalog',
      'commerce_product.uc_product_image',
      'node.field_image',
      'node.field_number',
      'node.field_tags',
      'taxonomy_term.uc_catalog_image',
    ];
    foreach ($field_storages as $field_storage) {
      /** @var \Drupal\field\Entity\FieldStorageConfig $storage */
      $storage = FieldStorageConfig::load($field_storage);
      $this->assertInstanceOf(FieldStorageConfig::class, $storage, "$field_storage is not an instance of FieldStorageConfig");
    }

    $storage = FieldStorageConfig::load('node.field_sustainability');
    $this->assertNull($storage);
    $storage = FieldStorageConfig::load('node.uc_product_image');
    $this->assertNull($storage);
  }

}
