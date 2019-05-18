<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 shipping flat rate source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\ShippingFlatRate
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ShippingFlatRateTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce',
    'commerce_tax',
    'commerce_price',
    'migrate_drupal',
    'commerce_migrate_commerce',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['commerce_flat_rate_service'] = [
      [
        'name' => 'shipping_1',
        'title' => 'Sample Shipping',
        'display_title' => 'Flat Rate',
        'description' => 'Standard',
        'rule_component' => '',
        'amount' => '1000',
        'currency_code' => 'CAD',
        'data' => 'a:0:{}',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'name' => 'shipping_1',
        'title' => 'Sample Shipping',
        'display_title' => 'Flat Rate',
        'description' => 'Standard',
        'rule_component' => '',
        'amount' => '1000',
        'currency_code' => 'CAD',
        'data' => [],
      ],
    ];

    return $tests;
  }

}
