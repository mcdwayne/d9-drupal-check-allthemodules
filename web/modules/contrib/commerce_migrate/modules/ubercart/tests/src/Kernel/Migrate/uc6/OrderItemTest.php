<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests order item migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class OrderItemTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'content_translation',
    'language',
    'migrate_plus',
    'path',
    'profile',
    'state_machine',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateOrderItems();
  }

  /**
   * Test order item migration.
   */
  public function testOrderItem() {
    $order_item = [
      'id' => 2,
      'order_id' => NULL,
      'created' => '1492989920',
      'changed' => '1508916762',
      'purchased_entity_id' => 3,
      'quantity' => '1.00',
      'title' => 'Fairy cake',
      'unit_price' => '1500.000000',
      'unit_price_currency_code' => 'NZD',
      'total_price' => '1500.000000',
      'total_price_currency_code' => 'NZD',
      'uses_legacy_adjustments' => '1',
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Handling',
          'amount' => new Price('60', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 3,
      'order_id' => NULL,
      'created' => '1492868907',
      'changed' => '1523578137',
      'purchased_entity_id' => 1,
      'quantity' => '1.00',
      'title' => 'Bath Towel',
      'unit_price' => '20.000000',
      'unit_price_currency_code' => 'NZD',
      'total_price' => '20.000000',
      'total_price_currency_code' => 'NZD',
      'uses_legacy_adjustments' => '1',
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Service charge',
          'amount' => new Price('1.99', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
        new Adjustment([
          'type' => 'custom',
          'label' => 'Handling',
          'amount' => new Price('1.4', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 4,
      'order_id' => NULL,
      'created' => '1492868907',
      'changed' => '1523578137',
      'purchased_entity_id' => 2,
      'quantity' => '1.00',
      'title' => 'Beach Towel',
      'unit_price' => '15.000000',
      'unit_price_currency_code' => 'NZD',
      'total_price' => '15.000000',
      'total_price_currency_code' => 'NZD',
      'uses_legacy_adjustments' => '1',
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Service charge',
          'amount' => new Price('1.99', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
        new Adjustment([
          'type' => 'custom',
          'label' => 'Handling',
          'amount' => new Price('1.4', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);
    $order_item = [
      'id' => 5,
      'order_id' => NULL,
      'created' => '1511148641',
      'changed' => '1511149246',
      'purchased_entity_id' => 4,
      'quantity' => '1.00',
      'title' => 'Magdalenas',
      'unit_price' => '20.000000',
      'unit_price_currency_code' => 'NZD',
      'total_price' => '20.000000',
      'total_price_currency_code' => 'NZD',
      'uses_legacy_adjustments' => '1',
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Handling',
          'amount' => new Price('0.8', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 6,
      'order_id' => NULL,
      'created' => '1502996811',
      'changed' => '1523578318',
      'purchased_entity_id' => 4,
      'quantity' => '1.00',
      'title' => 'Golgafrincham B-Ark',
      'unit_price' => '6000000000.000000',
      'unit_price_currency_code' => 'NZD',
      'total_price' => '6000000000.000000',
      'total_price_currency_code' => 'NZD',
      'uses_legacy_adjustments' => '1',
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Handling',
          'amount' => new Price('240000000', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 7,
      'order_id' => NULL,
      'created' => '1526437863',
      'changed' => '1526437864',
      'purchased_entity_id' => 2,
      'quantity' => '1.00',
      'title' => 'Beach Towel',
      'unit_price' => '18.000000',
      'unit_price_currency_code' => 'NZD',
      'total_price' => '18.000000',
      'total_price_currency_code' => 'NZD',
      'uses_legacy_adjustments' => '1',
      'adjustments' => [],
    ];
    $this->assertOrderItem($order_item);

    // Test that both product and order are linked.
    $order_item = OrderItem::load(2);
    $product = $order_item->getPurchasedEntity();
    $this->assertNotNull($product);
    $this->assertEquals(3, $product->id());
  }

}
