<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart order product source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\OrderProduct
 * @group commerce_migrate_uc
 */
class OrderProductCurrencyTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_ubercart'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['uc_order_products'] =
      [
        [
          'order_product_id' => '1',
          'order_id' => '1',
          'nid' => '1',
          'title' => 'Product 1',
          'manufacturer' => 'Someone',
          'model' => 'Product 1 - 001',
          'qty' => '2',
          'cost' => '500.00000',
          'price' => '600.00000',
          'weight' => '2',
          'data' => 'a:2:{s:9:"shippable";s:1:"1";s:6:"module";s:10:"uc_product";}',
        ],
      ];
    $tests[0]['source_data']['uc_order_line_items'] =
      [
        [
          'line_item_id' => '1',
          'order_id' => '1',
          'type' => 'shipping',
          'title' => 'Z Transport',
          'amount' => '9.99',
          'weight' => '1',
          'data' => 'N;',
        ],
        [
          'line_item_id' => '2',
          'order_id' => '1',
          'type' => 'custom',
          'title' => 'xyz',
          'amount' => '5.00',
          'weight' => '2',
          'data' => 'N;',
        ],
      ];
    $tests[0]['source_data']['uc_orders'] =
      [
        [
          'order_id' => '1',
          'uid' => '2',
          'order_status' => 'payment_received',
          'order_total' => '22.99000',
          'product_count' => '2',
          'primary_email' => 'f.bar@example.com',
          'delivery_first_name' => '',
          'delivery_last_name' => '',
          'delivery_phone' => '',
          'delivery_company' => '',
          'delivery_street1' => '',
          'delivery_street2' => '',
          'delivery_city' => '',
          'delivery_zone' => '',
          'delivery_postal_code' => '',
          'delivery_country' => '',
          'billing_first_name' => 'Foo',
          'billing_last_name' => 'Bar',
          'billing_phone' => '123-4567',
          'billing_company' => 'Acme',
          'billing_street1' => '1 Coyote Way',
          'billing_street2' => 'Utah',
          'billing_city' => 'Salt Lake',
          'billing_zone' => '58',
          'billing_postal_code' => '11111',
          'billing_country' => '840',
          'payment_method' => 'cod',
          'data' => 'a:0{}',
          'created' => '1492868907',
          'modified' => '1498620003',
          'host' => '192.168.0.2',
          'currency' => 'NZD',
        ],
      ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'order_id' => '1',
        'order_product_id' => '1',
        'nid' => '1',
        'title' => 'Product 1',
        'qty' => '2',
        'price' => '600.00000',
        'data' => [
          'shippable' => 1,
        ],
        'created' => '1492868907',
        'modified' => '1498620003',
        'currency' => 'NZD',
        'adjustments' => [
          [
            'amount' => '5.00',
            'line_item_id' => '2',
            'order_id' => '1',
            'type' => 'custom',
            'title' => 'xyz',
            'weight' => '2',
            'data' => 'N;',
            'currency_code' => 'NZD',
          ],
        ],
      ],
    ];

    return $tests;
  }

}
