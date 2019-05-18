<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Ubercart order plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\Order
 *
 * @group commerce_migrate
 * @group commerce_migrate_ubercart_uc
 */
class OrderTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate_ubercart',
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
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
          'data' => 'a:0:{}',
          'created' => '1492868907',
          'modified' => '1498620003',
          'host' => '192.168.0.2',
          'currency' => 'USD',
        ],
        [
          'order_id' => '2',
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
          'billing_phone' => '555-4567',
          'billing_company' => 'Acme',
          'billing_street1' => '1 Coyote Way',
          'billing_street2' => 'Utah',
          'billing_city' => 'Salt Lake',
          'billing_zone' => '58',
          'billing_postal_code' => '11111',
          'billing_country' => '840',
          'payment_method' => 'cod',
          'data' => 'a:0:{}',
          'created' => '1492868907',
          'modified' => '1498630003',
          'host' => '192.168.0.2',
          'currency' => 'USD',
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
          'order_id' => '2',
          'type' => 'custom',
          'title' => 'xyz',
          'amount' => '5.00',
          'weight' => '2',
          'data' => 'N;',
        ],
      ];
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
        [
          'order_product_id' => '3',
          'order_id' => '1',
          'nid' => '4',
          'title' => 'Towel',
          'manufacturer' => 'Acme',
          'model' => 'Towel - 001',
          'qty' => '2',
          'cost' => '500.00000',
          'price' => '600.00000',
          'weight' => '2',
          'data' => 'a:2:{s:9:"shippable";s:1:"1";s:6:"module";s:10:"uc_product";}',
        ],
        [
          'order_product_id' => '4',
          'order_id' => '1',
          'nid' => '3',
          'title' => 'Babel fish',
          'manufacturer' => 'Someone',
          'model' => 'Bable fish - 001',
          'qty' => '2',
          'cost' => '500.00000',
          'price' => '600.00000',
          'weight' => '2',
          'data' => 'a:2:{s:9:"shippable";s:1:"1";s:6:"module";s:10:"uc_product";}',
        ],
        [
          'order_product_id' => '9',
          'order_id' => '2',
          'nid' => '5',
          'title' => 'Cake',
          'manufacturer' => 'Someone',
          'model' => 'Cake - 001',
          'qty' => '2',
          'cost' => '500.00000',
          'price' => '600.00000',
          'weight' => '2',
          'data' => 'a:2:{s:9:"shippable";s:1:"1";s:6:"module";s:10:"uc_product";}',
        ],
      ];
    $tests[0]['source_data']['uc_order_comments'] = [
      [
        'comment_id' => '2',
        'order_id' => '2',
        'uid' => '0',
        'order_status' => 'pending',
        'notified' => '1',
        'message' => 'I was right.',
        'created' => '1492989931',
      ],
    ];
    $tests[0]['source_data']['uc_order_admin_comments'] = [
      [
        'comment_id' => '3',
        'order_id' => '2',
        'uid' => '0',
        'message' => 'Order created through website.',
        'created' => '1492989939',
      ],
    ];
    $tests[0]['source_data']['uc_order_log'] = [
      [
        'order_log_id' => '2',
        'order_id' => '2',
        'uid' => '0',
        'changes' => "<div class=\"item-list\"><ul><li class=\"first last\">Order status changed from <em class=\"placeholder\">In checkout</em> to <em class=\"placeholder\">Pending</em>.</li>\n</ul></div>",
        'created' => '1493327903',
      ],
    ];
    // The expected results.
    $tests[0]['expected_data'] =
      [
        [
          'order_id' => '1',
          'uid' => '2',
          'order_status' => 'payment_received',
          'primary_email' => 'f.bar@example.com',
          'data' => [],
          'created' => '1492868907',
          'modified' => '1498620003',
          'host' => '192.168.0.2',
          'order_item_ids' => [1, 3, 4],
          'adjustments' => [
            [
              'line_item_id' => '1',
              'order_id' => '1',
              'type' => 'custom',
              'title' => 'Z Transport',
              'amount' => '9.99',
              'weight' => '1',
              'data' => 'N;',
              'currency_code' => 'USD',
            ],
          ],
        ],
        [
          'order_id' => '2',
          'uid' => '2',
          'order_status' => 'payment_received',
          'primary_email' => 'f.bar@example.com',
          'data' => [],
          'created' => '1492868907',
          'modified' => '1498630003',
          'host' => '192.168.0.2',
          'order_item_ids' => [9],
          'adjustments' => [
            [
              'line_item_id' => '2',
              'order_id' => '2',
              'type' => 'custom',
              'title' => 'xyz',
              'amount' => '5.00',
              'weight' => '2',
              'data' => 'N;',
              'currency_code' => 'USD',
            ],
          ],
          'order_comments' => [
            [
              'value' => 'I was right.',
              'format' => NULL,
            ],
          ],
          'order_admin_comments' => [
            [
              'value' => 'Order created through website.',
              'format' => NULL,
            ],
          ],
          'order_logs' => [
            [
              'value' => "<div class=\"item-list\"><ul><li class=\"first last\">Order status changed from <em class=\"placeholder\">In checkout</em> to <em class=\"placeholder\">Pending</em>.</li>\n</ul></div>",
              'format' => NULL,
            ],
          ],
        ],
      ];
    return $tests;
  }

}
