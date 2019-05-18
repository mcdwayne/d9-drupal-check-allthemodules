<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product variation migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProductVariationTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_store',
    'commerce_product',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateProductVariations();
  }

  /**
   * Test product variation migration from Drupal 7 Commerce to Drupal 8.
   */
  public function testProductVariation() {
    $variation = [
      'id' => 1,
      'type' => 'bags_cases',
      'uid' => '1',
      'sku' => 'TOT1-GRN-OS',
      'price' => '16.000000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Tote Bag 1',
      'order_item_type_id' => 'product',
      'created_time' => '1493287314',
      'changed_time' => '1493287350',
    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 11,
      'type' => 'hats',
      'uid' => '1',
      'sku' => 'HAT1-GRY-OS',
      'price' => '16.000000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Hat 1',
      'order_item_type_id' => 'product',
      'created_time' => '1493287364',
      'changed_time' => '1493287400',

    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 12,
      'type' => 'hats',
      'uid' => '1',
      'sku' => 'HAT2-BLK-OS',
      'price' => '12.000000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Hat 2',
      'order_item_type_id' => 'product',
      'created_time' => '1493287369',
      'changed_time' => '1493287405',
    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 12,
      'type' => 'hats',
      'uid' => '1',
      'sku' => 'HAT2-BLK-OS',
      'price' => '12.000000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Hat 2',
      'order_item_type_id' => 'product',
      'created_time' => '1493287369',
      'changed_time' => '1493287405',
    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 19,
      'type' => 'shoes',
      'uid' => '1',
      'sku' => 'SHO2-PRL-04',
      'price' => '40.000000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Shoe 2',
      'order_item_type_id' => 'product',
      'created_time' => '1493287404',
      'changed_time' => '1493287440',

    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 20,
      'type' => 'shoes',
      'uid' => '1',
      'sku' => 'SHO2-PRL-05',
      'price' => '40.000000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Shoe 2',
      'order_item_type_id' => 'product',
      'created_time' => '1493287409',
      'changed_time' => '1493287445',

    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 28,
      'type' => 'storage_devices',
      'uid' => '1',
      'sku' => 'USB-BLU-08',
      'price' => '11.990000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Storage 1',
      'order_item_type_id' => 'product',
      'created_time' => '1493287449',
      'changed_time' => '1493287485',

    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 29,
      'type' => 'storage_devices',
      'uid' => '1',
      'sku' => 'USB-BLU-16',
      'price' => '17.990000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Storage 1',
      'order_item_type_id' => 'product',
      'created_time' => '1493287454',
      'changed_time' => '1493287490',
    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
    $variation = [
      'id' => 30,
      'type' => 'storage_devices',
      'uid' => '1',
      'sku' => 'USB-BLU-32',
      'price' => '29.990000',
      'currency' => 'USD',
      'product_id' => NULL,
      'title' => 'Storage 1',
      'order_item_type_id' => 'product',
      'created_time' => '1493287459',
      'changed_time' => '1493287495',
    ];
    $this->assertProductVariationEntity($variation['id'], $variation['type'], $variation['uid'], $variation['sku'], $variation['price'], $variation['currency'], $variation['product_id'], $variation['title'], $variation['order_item_type_id'], $variation['created_time'], $variation['changed_time']);
  }

}
