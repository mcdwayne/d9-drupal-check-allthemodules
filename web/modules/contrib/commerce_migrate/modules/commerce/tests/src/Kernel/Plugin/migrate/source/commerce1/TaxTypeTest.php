<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 tax type source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\TaxType
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class TaxTypeTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['commerce_tax_rate'] = [
      [
        'name' => 'sample_michigan_sales_tax',
        'title' => 'Sample Michigan Sales Tax 6%',
        'display_title' => 'Sample Michigan Sales Tax 6%',
        'description' => '',
        'rate' => '0.06',
        'type' => 'sales_tax',
        'default_rules_component' => '',
        'module' => 'commerce_tax_ui',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'name' => 'sample_michigan_sales_tax',
        'title' => 'Sample Michigan Sales Tax 6%',
        'display_title' => 'Sample Michigan Sales Tax 6%',
        'description' => '',
        'rate' => '0.06',
        'type' => 'sales_tax',
        'default_rules_component' => '',
        'module' => 'commerce_tax_ui',
        'default_country' => '',
      ],
    ];

    $tests[1]['source_data']['commerce_tax_rate'] = [
      [
        'name' => 'Hawaii_sales_tax',
        'title' => ' Hawaii Sales Tax 6%',
        'display_title' => ' Hawaii Sales Tax 6%',
        'description' => '',
        'rate' => '0.06',
        'type' => 'sales_tax',
        'default_rules_component' => '',
        'module' => 'commerce_tax_ui',
      ],
    ];
    $tests[1]['source_data']['variable'] = [
      [
        'name' => 'site_default_country',
        'value' => 's:2:"NZ";',
      ],
    ];
    $tests[1]['expected_data'] = [
      [
        'name' => 'Hawaii_sales_tax',
        'title' => ' Hawaii Sales Tax 6%',
        'display_title' => ' Hawaii Sales Tax 6%',
        'description' => '',
        'rate' => '0.06',
        'type' => 'sales_tax',
        'default_rules_component' => '',
        'module' => 'commerce_tax_ui',
        'default_country' => 'NZ',
      ],
    ];

    return $tests;
  }

}
