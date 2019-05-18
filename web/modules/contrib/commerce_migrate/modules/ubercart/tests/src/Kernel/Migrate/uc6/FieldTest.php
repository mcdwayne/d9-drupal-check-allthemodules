<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Migrate field tests.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class FieldTest extends Ubercart6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'field',
    'migrate_plus',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product');
    $this->executeMigration('d6_field');
  }

  /**
   * Tests the Drupal 6 field to Drupal 8 migration.
   */
  public function testFields() {
    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage = FieldStorageConfig::load('commerce_product.field_image_cache');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
    $field_storage = FieldStorageConfig::load('commerce_product.field_sustain');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
    $field_storage = FieldStorageConfig::load('commerce_product.field_engine');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
    $field_storage = FieldStorageConfig::load('commerce_product.field_integer');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
    $field_storage = FieldStorageConfig::load('node.field_integer');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage);
  }

}
