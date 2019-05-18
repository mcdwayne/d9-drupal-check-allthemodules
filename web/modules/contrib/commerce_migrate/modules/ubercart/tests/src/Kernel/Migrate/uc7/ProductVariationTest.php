<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests Product variation migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ProductVariationTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'filter',
    'image',
    'menu_ui',
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
    $this->installEntitySchema('node');
    $this->installEntitySchema('view');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installConfig(static::$modules);
    $this->migrateStore();
    $this->migrateUsers(FALSE);
    $this->executeMigrations([
      'd7_taxonomy_vocabulary',
      'd7_comment_type',
      'd7_comment_field',
      'uc7_comment_type',
      'uc7_comment_field',
      'd7_field',
      'uc_attribute_field',
      'uc_product_attribute',
      'uc_attribute_field_instance',
      'd7_node_type',
      'uc7_product_type',
      'uc7_product_variation_type',
      'uc7_product_variation',
    ]);
  }

  /**
   * Test product variation migration.
   */
  public function testProductVariation() {
    $this->assertProductVariationEntity(1, 'product', '1', 'drink-001', '50.000000', 'USD', NULL, 'Breshtanti ale', 'default', '1493289860', NULL);
    $this->assertProductVariationEntity(2, 'product', '1', 'drink-002', '100.000000', 'USD', NULL, 'Romulan ale', 'default', '1493326300', NULL);
    $this->assertProductVariationEntity(3, 'entertainment', '1', 'Holosuite-001', '40.000000', 'USD', NULL, 'Holosuite 1', 'default', '1493326429', NULL);
  }

}
