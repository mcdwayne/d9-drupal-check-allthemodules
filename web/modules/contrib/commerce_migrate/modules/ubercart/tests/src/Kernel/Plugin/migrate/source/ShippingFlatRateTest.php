<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Ubercart store source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ShippingFlatRate
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc
 */
class ShippingFlatRateTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate_drupal',
    'commerce_migrate_ubercart',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['uc_flatrate_methods'] = [
      [
        'mid' => '1',
        'title' => 'VanMan',
        'label' => 'Van man',
        'base_rate' => '2.00',
        'product_rate' => '3.25',
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'currency_code',
        'currency_code' => 's:3:"USD";',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'mid' => '1',
        'title' => 'VanMan',
        'label' => 'Van man',
        'base_rate' => '2.00',
        'product_rate' => '3.25',
        'currency_code' => 'USD',
      ],
    ];

    return $tests;
  }

}
