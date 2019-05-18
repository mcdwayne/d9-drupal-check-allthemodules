<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Ubercart tax type source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6\TaxType
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class TaxTypeTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['uc_taxes'] = [
      [
        'id' => '1',
        'name' => 'Handling',
        'rate' => '0.05',
        'shippable' => '0',
        'taxed_product_types' => 'a:0:{}',
        'taxed_line_items' => 'a:0:{}',
        'weight' => 0,
      ],
      [
        'id' => '2',
        'name' => 'Fuel',
        'rate' => '0.25',
        'shippable' => '0',
        'taxed_product_types' => 'a:0:{}',
        'taxed_line_items' => 'a:1:{s:3:"tax";s:3:"tax";}',
        'weight' => 0,
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'uc_store_country',
        'value' => 's:3:"124";',
      ],
    ];
    $tests[0]['source_data']['uc_countries'] = [
      [
        'country_id' => '124',
        'country_name' => 'Canada',
        'country_iso_code_2' => 'CA',
        'country_iso_code_3' => 'CAN',
        'version' => '2',
        'weight' => '0',
      ],
      [
        'country_id' => '840',
        'country_name' => 'United States',
        'country_iso_code_2' => 'US',
        'country_iso_code_3' => 'USA',
        'version' => '1',
        'weight' => '0',
      ],
    ];
    $tests[0]['expected_data'] = [
      [
        'id' => '1',
        'name' => 'Handling',
        'rate' => '0.05',
        'country_iso_code_2' => 'CA',
        'shippable' => '0',
        'taxed_product_types' => [],
        'taxed_line_items' => [],
        'weight' => 0,
      ],
      [
        'id' => '2',
        'name' => 'Fuel',
        'rate' => '0.25',
        'country_iso_code_2' => 'CA',
        'shippable' => '0',
        'taxed_product_types' => [],
        'taxed_line_items' => unserialize('a:1:{s:3:"tax";s:3:"tax";}'),
        'weight' => 0,
      ],
    ];
    return $tests;
  }

}
