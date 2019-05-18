<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests order migration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class OrderTest extends Ubercart7TestBase {

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
    'node',
    'path',
    'profile',
    'state_machine',
    'telephone',
    'text',
    'filter',
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
  public function testUbercartOrder() {
    $order = [
      'id' => 1,
      'type' => 'default',
      'number' => '1',
      'store_id' => '1',
      'created_time' => '1493326662',
      'changed_time' => '1536901828',
      'completed_time' => NULL,
      'email' => 'tomparis@example.com',
      'ip_address' => '172.19.0.2',
      'customer_id' => '2',
      'placed_time' => '1536901828',
      'total_price' => '112.000000',
      'total_price_currency' => 'USD',
      'label_value' => 'validation',
      'label_rendered' => 'validation',
      'order_items_ids' => ['1'],
      'billing_profile' => ['1', '1'],
      'cart' => NULL,
      'data' => [],
      'adjustments' => [
        new Adjustment([
          'type' => 'custom',
          'label' => 'Shipping',
          'amount' => new Price('2', 'USD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
        new Adjustment([
          'type' => 'custom',
          'label' => 'Station maintenance',
          'amount' => new Price('5', 'USD'),
          'percentage' => NULL,
          'source_id' => 'custom',
          'included' => FALSE,
          'locked' => TRUE,
        ]),
      ],
      'order_admin_comments' => [
        [
          'value' => 'Order created through website.',
        ],
        [
          'value' => 'Admin comment 1',
        ],
        [
          'value' => 'Admin comment 2',
        ],
      ],
      'order_comments' => [],
      'order_logs' => [
        0 => [
          'value' => "Added $5.00 for Station maintenance.\n",
        ],
        1 => [
          'value' => "Order status changed from In checkout to Pending.\n",
        ],
        2 => [
          'value' => "delivery_street1 changed from Level 12 to .
delivery_city changed from USS Voyager to San Fransisco.
delivery_zone changed from 66 to 12.
delivery_country changed from 124 to 840.
billing_street1 changed from Level 12 to .
billing_city changed from Montgomery to San Fransisco.
billing_postal_code changed from 1234 to 74656.\n",
        ],
        3 => [
          'value' => "COD payment for $50.00 entered.\n",
        ],
        4 => [
          'value' => "COD payment for -$40.00 entered.\n",
        ],
        5 => [
          'value' => "Free order payment for $60.00 entered.\n",
        ],
        6 => [
          'value' => "Free order payment for $60.00 deleted.\n",
        ],
        7 => [
          'value' => "COD payment for $53.00 entered.\n",
        ],
        8 => [
          'value' => "COD payment for -$60.00 entered.\n",
        ],
      ],
    ];
    $this->assertUbercartOrder($order);

    $order = [
      'id' => 2,
      'type' => 'default',
      'number' => '2',
      'store_id' => '1',
      'created_time' => '1536901552',
      'changed_time' => '1536963792',
      'completed_time' => '1536963792',
      'email' => 'harrykim@example.com',
      'label' => 'completed',
      'ip_address' => '172.19.0.2',
      'customer_id' => '4',
      'placed_time' => '1536963792',
      'total_price' => '440.400000',
      'total_price_currency' => 'USD',
      'label_value' => 'completed',
      'label_rendered' => 'Completed',
      'order_items_ids' => ['2', '3'],
      'billing_profile' => ['2', '2'],
      'cart' => NULL,
      'data' => [],
      'adjustments' => [],
      'order_admin_comments' => [
        [
          'value' => 'Order created by the administration.',
        ],
      ],
      'order_comments' => [],
      'order_logs' => [
        0 => [
          'value' => "Added (1) Romulan ale to order.\n",
        ],
        1 => [
          'value' => "order_total changed from 100.1 to 0.
product_count changed from 1 to 0.
delivery_zone changed from 0 to 0.
billing_zone changed from 0 to 0.
payment_method changed from  to free_order.\n",
        ],
        2 => [
          'value' => "delivery_first_name changed from  to Tom.
delivery_last_name changed from  to Paris.
delivery_city changed from  to San Fransisco.
delivery_zone changed from 0 to 12.
delivery_country changed from 124 to 840.
billing_first_name changed from  to Tom.
billing_last_name changed from  to Paris.
billing_city changed from  to San Fransisco.
billing_zone changed from 0 to 0.
billing_country changed from 124 to 840.
payment_method changed from free_order to cod.\n",
        ],
        3 => [
          'value' => "Added (1) Holosuite 1 to order.\n",
        ],
        4 => [
          'value' => "order_total changed from 140.1 to 100.1.
product_count changed from 2 to 1.
primary_email changed from tomparis@example.com to harrykim@example.com.
delivery_first_name changed from Tom to Harry.
delivery_last_name changed from Paris to Kim.
billing_first_name changed from Tom to Harry.
billing_last_name changed from Paris to Kim.
billing_zone changed from 0 to 12.\n",

        ],
        5 => [
          'value' => "uid changed from 2 to 4.\n",
        ],
        6 => [
          'value' => "billing_street1 changed from  to 33 First Street.\n",
        ],
        7 => [
          'value' => "COD payment for $400.00 entered.\n",
        ],
        8 => [
          'value' => "COD payment for $40.40 entered.\n",
        ],
        9 => [
          'value' => "Order status changed from Pending to Payment received.\n",
        ],
      ],
    ];
    $this->assertUbercartOrder($order);

    $order = [
      'id' => 3,
      'type' => 'default',
      'number' => '3',
      'store_id' => '1',
      'created_time' => '1536902338',
      'changed_time' => '1536964646',
      'completed_time' => NULL,
      'email' => 'tomparis@example.com',
      'label' => 'completed',
      'ip_address' => '172.19.0.2',
      'customer_id' => '2',
      'placed_time' => '1536964646',
      'total_price' => NULL,
      'total_price_currency' => 'USD',
      'label_value' => 'validation',
      'label_rendered' => 'validation',
      'order_items_ids' => [],
      'billing_profile' => ['1', '3'],
      'cart' => NULL,
      'data' => [],
      'adjustments' => [],
      'order_admin_comments' => [
        [
          'value' => 'Order created by the administration.',
        ],
      ],
      'order_comments' => [],
      'order_logs' => [
        0 => [
          'value' => "delivery_first_name changed from  to Tom.
delivery_last_name changed from  to Paris.
delivery_street1 changed from  to Level 12.
delivery_city changed from  to Starship Voyager.
delivery_zone changed from 0 to 0.
billing_first_name changed from  to Harry.
billing_last_name changed from  to Kim.
billing_zone changed from 0 to 12.
billing_country changed from 124 to 840.
payment_method changed from  to free_order.\n",
        ],
        1 => [
          'value' => "delivery_zone changed from 0 to 0.
billing_street1 changed from  to 11 Somewhere St.
billing_city changed from  to San Fransisco.\n",
        ],
        2 => [
          'value' => "delivery_zone changed from 0 to 0.
billing_first_name changed from Harry to Tom.
billing_last_name changed from Kim to Paris.
billing_street1 changed from 11 Somewhere St to Level 12.
billing_city changed from San Fransisco to Starship Voyager.
billing_zone changed from 12 to 0.
billing_country changed from 840 to 124.\n",
        ],
      ],
    ];
    $this->assertUbercartOrder($order);

    $order = [
      'id' => 4,
      'type' => 'default',
      'number' => '4',
      'store_id' => '1',
      'created_time' => '1536902428',
      'changed_time' => '1536902428',
      'completed_time' => NULL,
      'email' => 'harrykim@example.com',
      'label' => 'completed',
      'ip_address' => '127.0.0.1',
      'customer_id' => '4',
      'placed_time' => '1536902428',
      'total_price' => '50.500000',
      'total_price_currency' => 'USD',
      'label_value' => 'validation',
      'label_rendered' => 'validation',
      'order_items_ids' => ['4'],
      'billing_profile' => ['2', '4'],
      'cart' => NULL,
      'data' => [],
      'adjustments' => [],
      'order_admin_comments' => [
        [
          'value' => 'Order created by the administration.',
        ],
      ],
      'order_comments' => [],
      'order_logs' => [
        0 => [
          'value' => "Added (1) Breshtanti ale to order.\n",
        ],
      ],
    ];
    $this->assertUbercartOrder($order);
  }

}
