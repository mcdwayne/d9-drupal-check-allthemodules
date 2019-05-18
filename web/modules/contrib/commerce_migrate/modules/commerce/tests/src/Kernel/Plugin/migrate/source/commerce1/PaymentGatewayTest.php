<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 payment source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\PaymentGateway
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce
 */
class PaymentGatewayTest extends MigrateSqlSourceTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_commerce'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['commerce_payment_transaction'] = [
      [
        'transaction_id' => '1',
        'revision_id' => '1',
        'uid' => '1',
        'order_id' => '2',
        'payment_method' => 'commerce_payment_example',
      ],
    ];

    // The expected results are identical to the source data.
    $tests[0]['expected_data'] = [
      [
        'payment_method' => 'commerce_payment_example',
      ],
    ];

    return $tests;
  }

}
