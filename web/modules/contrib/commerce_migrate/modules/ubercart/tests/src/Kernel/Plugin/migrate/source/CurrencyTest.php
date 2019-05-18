<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Ubercart currency source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\Currency
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class CurrencyTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce',
    'commerce_price',
    'migrate_drupal',
    'commerce_migrate_ubercart',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'uc_currency_code',
        'value' => 's:3:"NZD";',
      ],
      [
        'name' => 'uc_currency_prec',
        'value' => 's:1:"2";',
      ],
      [
        'name' => 'uc_currency_sign',
        'value' => 's:1:"$";',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'uc_currency_code' => 'NZD',
        'currency_name' => 'New Zealand Dollar',
        'numeric_code' => '554',
      ],
    ];
    $tests[0]['expected_count'] = NULL;
    $tests[0]['configuration'] = [
      'variables' =>
        [
          'uc_currency_code',
          'uc_currency_prec',
          'uc_currency_sign',
        ],
    ];
    return $tests;
  }

}
