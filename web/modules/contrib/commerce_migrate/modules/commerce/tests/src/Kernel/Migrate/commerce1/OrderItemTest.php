<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests order item migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class OrderItemTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_store',
    'migrate_plus',
    'path',
    'profile',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateOrderItems();
  }

  /**
   * Test line item migration from Drupal 7 to 8.
   */
  public function testOrderItem() {
    $order_item = [
      'id' => 1,
      'order_id' => NULL,
      'created' => '1493287435',
      'changed' => '1493287440',
      'purchased_entity_id' => '12',
      'quantity' => '1.00',
      'title' => 'Hat 2',
      'unit_price' => '12.000000',
      'unit_price_currency_code' => 'USD',
      'total_price' => '12.000000',
      'total_price_currency_code' => 'USD',
      'uses_legacy_adjustments' => '0',
      'adjustments' => [],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 2,
      'order_id' => NULL,
      'created' => '1493287445',
      'changed' => '1493287450',
      'purchased_entity_id' => '12',
      'quantity' => '1.00',
      'title' => 'Hat 2',
      'unit_price' => '12.000000',
      'unit_price_currency_code' => 'USD',
      'total_price' => '12.000000',
      'total_price_currency_code' => 'USD',
      'uses_legacy_adjustments' => '0',
      'adjustments' => [],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 3,
      'order_id' => NULL,
      'created' => '1493287455',
      'changed' => '1493287460',
      'purchased_entity_id' => '45',
      'quantity' => '1.00',
      'title' => 'Tshirt 3',
      'unit_price' => '38.000000',
      'unit_price_currency_code' => 'USD',
      'total_price' => '38.000000',
      'total_price_currency_code' => 'USD',
      'uses_legacy_adjustments' => '0',
      'adjustments' => [],
    ];
    $this->assertOrderItem($order_item);

    // No shipping line items.
    $this->assertNull(OrderItem::load(11));
    $this->assertNull(OrderItem::load(12));
    $this->assertNull(OrderItem::load(13));

    $order_item = [
      'id' => 14,
      'order_id' => NULL,
      'created' => '1541732400',
      'changed' => '1541732476',
      'purchased_entity_id' => 10,
      'quantity' => '3.00',
      'title' => 'Water Bottle 1',
      'unit_price' => '16.000000',
      'unit_price_currency_code' => 'USD',
      'total_price' => '48.000000',
      'total_price_currency_code' => 'USD',
      'uses_legacy_adjustments' => '0',
      'adjustments' => [
        new Adjustment([
          'type' => 'tax',
          'label' => 'Sample NZ Sales Tax 6%',
          'amount' => new Price('2.88', 'USD'),
          'percentage' => '0.06',
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 27,
      'order_id' => NULL,
      'created' => '1543271966',
      'changed' => '1543271966',
      'purchased_entity_id' => NULL,
      'quantity' => '1.00',
      'title' => '',
      'unit_price' => '0.000000',
      'unit_price_currency_code' => 'USD',
      'total_price' => '0.000000',
      'total_price_currency_code' => 'USD',
      'uses_legacy_adjustments' => '0',
      'adjustments' => [
        new Adjustment([
          'type' => 'promotion',
          'label' => 'Peace day discount',
          'amount' => new Price('-24', 'USD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 28,
      'order_id' => NULL,
      'created' => '1544649230',
      'changed' => '1544649300',
      'purchased_entity_id' => 1,
      'quantity' => '10.00',
      'title' => 'Tote Bag 1',
      'unit_price' => '16.000000',
      'unit_price_currency_code' => 'USD',
      'total_price' => '160.000000',
      'total_price_currency_code' => 'USD',
      'uses_legacy_adjustments' => '0',
      'adjustments' => [
        new Adjustment([
          'type' => 'promotion',
          'label' => 'Bag discount',
          'amount' => new Price('-32', 'USD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
        new Adjustment([
          'type' => 'tax',
          'label' => 'Sample NZ Sales Tax 6%',
          'amount' => new Price('7.68', 'USD'),
          'percentage' => '0.06',
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

    $order_item = [
      'id' => 29,
      'order_id' => NULL,
      'created' => '1544649280',
      'changed' => '1544649280',
      'purchased_entity_id' => 10,
      'quantity' => '1.00',
      'title' => 'Water Bottle 1',
      'unit_price' => '16.000000',
      'unit_price_currency_code' => 'USD',
      'total_price' => '16.000000',
      'total_price_currency_code' => 'USD',
      'uses_legacy_adjustments' => '0',
      'adjustments' => [
        new Adjustment([
          'type' => 'tax',
          'label' => 'Sample NZ Sales Tax 6%',
          'amount' => new Price('0.96', 'USD'),
          'percentage' => '0.06',
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
    ];
    $this->assertOrderItem($order_item);

  }

}
