<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart order payment source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\OrderPayment
 * @group commerce_migrate
 * @group commerce_migrate_uc
 */
class OrderPaymentTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_ubercart'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['uc_payment_receipts'] = [
      [
        'receipt_id' => '1',
        'order_id' => '1',
        'method' => 'check',
        'title' => 'Product 1',
        'amount' => '2.00000',
        'uid' => 1,
        'data' => '',
        'comment' => 'Receipt 1',
        'received' => '1234567890',
      ],
      [
        'receipt_id' => '2',
        'order_id' => '3',
        'method' => 'cash',
        'title' => 'Product 1',
        'amount' => '6.00000',
        'uid' => 1,
        'data' => '',
        'comment' => 'Receipt 2',
        'received' => '9876543211',
      ],
    ];

    $tests[0]['source_data']['uc_orders'] = [
      [
        'order_id' => 1,
        'created' => '1234567890',
        'modified' => '1234567890',
      ],
      [
        'order_id' => 3,
        'created' => '1234567890',
        'modified' => '1234567890',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'receipt_id' => '1',
        'order_id' => '1',
        'method' => 'check',
        'title' => 'Product 1',
        'amount' => '2.00000',
        'uid' => 1,
        'data' => '',
        'comment' => 'Receipt 1',
        'received' => '1234567890',
      ],
      [
        'receipt_id' => '2',
        'order_id' => '3',
        'method' => 'cash',
        'title' => 'Product 1',
        'amount' => '6.00000',
        'uid' => 1,
        'data' => '',
        'comment' => 'Receipt 2',
        'received' => '9876543211',
      ],
    ];

    return $tests;
  }

}
