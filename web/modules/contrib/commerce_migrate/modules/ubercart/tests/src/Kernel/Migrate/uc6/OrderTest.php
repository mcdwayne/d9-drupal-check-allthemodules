<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests order migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class OrderTest extends Ubercart6TestBase {

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
    $this->migrateOrders();
  }

  /**
   * Test order migration.
   */
  public function testOrder() {
    $order = [
      'id' => 1,
      'type' => 'default',
      'number' => '1',
      'store_id' => '1',
      'created_time' => '1492868907',
      'changed_time' => '1523578137',
      'completed_time' => NULL,
      'email' => 'fordprefect@example.com',
      'ip_address' => '10.1.1.2',
      'customer_id' => '3',
      'placed_time' => '1523578137',
      'total_price' => '48.670000',
      'total_price_currency' => 'NZD',
      'label_value' => 'validation',
      'label_rendered' => 'validation',
      'order_items_ids' => ['3', '4'],
      'billing_profile' => ['1', '1'],
      'data' => [],
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Joopleberry transport',
          'amount' => new Price('3.5', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
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
      'cart' => NULL,
      'order_admin_comments' => [
        [
          'value' => 'Order created by the administration.',
        ],
        [
          'value' => 'Ford bought a new towel.',
        ],
      ],
      'order_comments' => [],
            // Skip testing logs.
      'order_logs' => NULL,
    ];
    $this->assertUbercartOrder($order);
    $order = [
      'id' => 2,
      'type' => 'default',
      'number' => '2',
      'store_id' => '1',
      'created_time' => '1492989920',
      'changed_time' => '1508916762',
      'completed_time' => '1508916762',
      'email' => 'trintragula@example.com',
      'label' => 'completed',
      'ip_address' => '10.1.1.2',
      'customer_id' => '5',
      'placed_time' => '1508916762',
      'total_price' => '2620.000000',
      'total_price_currency' => 'NZD',
      'label_value' => 'completed',
      'label_rendered' => 'Completed',
      'order_items_ids' => ['2'],
      'billing_profile' => ['2', '2'],
      'data' => [],
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Shipping',
          'amount' => new Price('1000', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
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
      'cart' => NULL,
      'order_admin_comments' => [
        [
          'value' => 'Order created through website.',
        ],
      ],
      'order_comments' => [
        [
          'value' => 'I was right.',
        ],
      ],
      'order_logs' => [
        0 => [
          'value' => "Order status changed from In checkout to Pending.\n",
        ],
        1 => [
          'value' => "COD payment for 2,500.00$ entered by 1.\n",
        ],
        2 => [
          'value' => "Order status changed from Pending to Payment received.\n",
        ],
        3 => [
          'value' => "COD payment for -900.00$ entered by 1.\n",
        ],
        4 => [
          'value' => "COD payment for 50.00$ entered by 1.\n",
        ],
        5 => [
          'value' => "COD payment for -800.00$ entered by 1.\n",
        ],
        6 => [
          'value' => "payment_method changed from  to cod.\n",
        ],
        7 => [
          'value' => "Added 60.00$ for Handling.\n",
        ],
      ],
    ];
    $this->assertUbercartOrder($order);

    $order = [
      'id' => 3,
      'type' => 'default',
      'number' => '3',
      'store_id' => '1',
      'created_time' => '1511148641',
      'changed_time' => '1511149246',
      'completed_time' => '1511149246',
      'email' => 'zaphod@example.com',
      'label' => 'completed',
      'ip_address' => '10.1.1.2',
      'customer_id' => '4',
      'placed_time' => '1511149246',
      'total_price' => '41.600000',
      'total_price_currency' => 'NZD',
      'label_value' => 'completed',
      'label_rendered' => 'Completed',
      'order_items_ids' => ['5'],
      'billing_profile' => ['4', '4'],
      'data' => [],
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Shipping',
          'amount' => new Price('20', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
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
      'cart' => NULL,
      'order_admin_comments' => [
        [
          'value' => 'Order created by the administration.',
        ],
        [
          'value' => 'Dile al cliente que llegue cuando llegue',
        ],
      ],
      'order_comments' => [
        ['value' => 'Este pedido se mandará a España en breve.'],
      ],
      // Skip testing logs.
      'order_logs' => NULL,
    ];
    $this->assertUbercartOrder($order);

    $order = [
      'id' => 4,
      'type' => 'default',
      'number' => '4',
      'store_id' => '1',
      'created_time' => '1502996811',
      // Changed time is overwritten by Commerce when the status is Draft. The
      // source changed time is '1502996997'.
      'changed_time' => '1523578318',
      'completed_time' => NULL,
      'email' => 'trillian@example.com',
      'label' => 'completed',
      'ip_address' => '10.1.1.2',
      'customer_id' => '2',
      'placed_time' => NULL,
      'total_price' => '6480000006.000000',
      'total_price_currency' => 'NZD',
      'label_value' => 'draft',
      'label_rendered' => 'Draft',
      'order_items_ids' => ['6'],
      'billing_profile' => ['3', '3'],
      'data' => [
        ['paid_event_dispatched' => FALSE],
      ],
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Fluff transport',
          'amount' => new Price('6', 'NZD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
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
      'cart' => NULL,
      'order_admin_comments' => [],
      'order_comments' => [],
      // Skip testing logs.
      'order_logs' => NULL,
    ];
    $this->assertUbercartOrder($order);

    $order = [
      'id' => 5,
      'number' => '5',
      'type' => 'default',
      'store_id' => '1',
      'created_time' => '1526437863',
      // Changed time is overwritten by Commerce when the status is Draft. The
      // source changed time is '1526437864'.
      'changed_time' => '1526437864',
      'completed_time' => NULL,
      'email' => 'zaphod@example.com',
      'label' => 'in_checkout',
      'ip_address' => '10.1.1.2',
      'customer_id' => '4',
      'placed_time' => NULL,
      'total_price' => '18.000000',
      'total_price_currency' => 'NZD',
      'label_value' => 'draft',
      'label_rendered' => 'Draft',
      'order_items_ids' => ['7'],
      'billing_profile' => ['4', '5'],
      'data' => [
        ['paid_event_dispatched' => FALSE],
      ],
      'adjustments' => [],
      'cart' => NULL,
      'order_admin_comments' => [],
      'order_comments' => [],
      // Skip testing logs.
      'order_logs' => NULL,
    ];
    $this->assertUbercartOrder($order);
  }

}
