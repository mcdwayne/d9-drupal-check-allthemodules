<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product variation type migration.
 *
 * @requires commerce_shipping
 * @requires physical
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductVariationTypeTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_shipping',
    'commerce_store',
    'path',
    'profile',
    'physical',
    'state_machine',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_product_variation');
    $this->executeMigration('uc6_product_variation_type');
  }

  /**
   * Test product variation type migration from Ubercart 6 to Commerce 2.
   */
  public function testProductVariationType() {
    $type = [
      'id' => 'product',
      'label' => 'Product',
      'order_item_type_id' => 'default',
      'is_title_generated' => FALSE,
      'traits' => [
        'purchasable_entity_dimensions',
        'purchasable_entity_shippable',
      ],
    ];
    $this->assertProductVariationTypeEntity($type['id'], $type['label'], $type['order_item_type_id'], $type['is_title_generated'], $type['traits']);
    $type = [
      'id' => 'product_kit',
      'label' => 'Product kit',
      'order_item_type_id' => 'default',
      'is_title_generated' => FALSE,
      'traits' => [],
    ];
    $this->assertProductVariationTypeEntity($type['id'], $type['label'], $type['order_item_type_id'], $type['is_title_generated'], $type['traits']);

    $type = [
      'id' => 'ship',
      'label' => 'Ship',
      'order_item_type_id' => 'default',
      'is_title_generated' => FALSE,
      'traits' => [
        'purchasable_entity_dimensions',
        'purchasable_entity_shippable',
      ],
    ];
    $this->assertProductVariationTypeEntity($type['id'], $type['label'], $type['order_item_type_id'], $type['is_title_generated'], $type['traits']);
  }

}
