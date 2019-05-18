<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\field\Entity\FieldConfig;

/**
 * Migrate field instance tests.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class FieldInstanceTest extends Ubercart6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'field',
    'migrate_plus',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateFields();
  }

  /**
   * Tests migration of field instances to products.
   */
  public function testFieldInstanceMigration() {
    $field = FieldConfig::load('commerce_product.product.field_image_cache');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field = FieldConfig::load('commerce_product.product.field_image_cache');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field = FieldConfig::load('commerce_product.product.field_integer');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field = FieldConfig::load('commerce_product.product.field_sustain');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field = FieldConfig::load('commerce_product.ship.field_engine');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field = FieldConfig::load('commerce_product.ship.field_image_cache');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $field = FieldConfig::load('commerce_product.ship.field_integer');
    $this->assertInstanceOf(FieldConfig::class, $field);

    $field = FieldConfig::load('node.page.field_integer');
    $this->assertInstanceOf(FieldConfig::class, $field);
  }

}
