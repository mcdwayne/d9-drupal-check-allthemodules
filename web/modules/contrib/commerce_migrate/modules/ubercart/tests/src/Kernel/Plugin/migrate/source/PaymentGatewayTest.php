<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Ubercart payment source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\PaymentGateway
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc
 */
class PaymentGatewayTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_ubercart'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['uc_payment_receipts'] = [
      [
        'receipt_id' => '1',
        'order_id' => '1',
        'method' => 'Check',
        'amount' => '45.23',
        'uid' => '1',
        'data' => NULL,
        'comment' => 'Just what I wanted',
        'received' => '1496231540',
      ],
    ];
    // The source data.
    $tests[0]['source_data']['uc_orders'] = [
      [
        'order_id' => '1',
        'uid' => '1',
        'order_status' => 'payment_received',
        'order_total' => '45.23',
        'product_count' => '3',
        'primary_email' => 'customer1@example.com',
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
        'billing_first_name' => '',
        'billing_last_name' => '',
        'billing_phone' => '',
        'billing_company' => '',
        'billing_street1' => '',
        'billing_street2' => '',
        'billing_city' => '',
        'billing_zone' => '',
        'billing_postal_code' => '',
        'billing_country' => '',
        'payment_method' => '',
        'data' => NULL,
        'created' => '1496230540',
        'modified' => '1496230640',
        'host' => '192.168.0.3',
        'currency' => 'NZD',
      ],
    ];
    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = [
      [
        'method' => 'Check',
      ],
    ];

    return $tests;
  }

}
