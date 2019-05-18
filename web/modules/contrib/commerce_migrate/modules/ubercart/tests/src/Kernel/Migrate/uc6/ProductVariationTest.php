<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Tests Product variation migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductVariationTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'content_translation',
    'language',
    'menu_ui',
    'migrate_plus',
    'path',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateProductVariations();
  }

  /**
   * Test product variation migration.
   */
  public function testProductVariation() {
    $this->assertProductVariationEntity(1, 'product', '1', 'towel-bath-001', '20.000000', 'NZD', '1', 'Bath Towel', 'default', '1492867780', NULL);
    $this->assertProductVariationEntity(2, 'product', '1', 'towel-beach-001', '15.000000', 'NZD', '2', 'Beach Towel', 'default', '1492989418', NULL);
    $this->assertProductVariationEntity(3, 'product', '1', 'Fairy-Cake-001', '1500.000000', 'NZD', '3', 'Fairy cake', 'default', '1492989703', NULL);
    $this->assertProductVariationEntity(4, 'ship', '1', 'ship-001', '6000000000.000000', 'NZD', '4', 'Golgafrincham B-Ark', 'default', '1500868190', NULL);
    $this->assertProductVariationEntity(5, 'ship', '1', 'ship-002', '123000000.000000', 'NZD', '5', 'Heart of Gold', 'default', '1500868361', NULL);
    $variation = ProductVariation::load(6);
    $this->assertNull($variation);
    $variation = ProductVariation::load(7);
    $this->assertNull($variation);
  }

}
