<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 billing profile source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\BillingProfile
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class BillingProfileTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_commerce'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['commerce_customer_profile'] = [
      [
        'profile_id' => '1',
        'revision_id' => '1',
        'type' => 'billing',
        'uid' => '3',
        'status' => '1',
        'created' => '1493287440',
        'changed' => '1493287440',
        'data' => NULL,
      ],
      [
        'profile_id' => '2',
        'revision_id' => '2',
        'type' => 'shipping',
        'uid' => '3',
        'status' => '1',
        'created' => '1493287450',
        'changed' => '1493287450',
        'data' => NULL,
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '2',
        'field_name' => 'commerce_unit_price',
        'type' => 'commerce_price',
        'module' => 'commerce_price',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '1',
        'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:18:"commerce_line_item";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:14:"currency_price";a:2:{i:0;s:6:"amount";i:1;s:13:"currency_code";}}}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'commerce_unit_price',
        'entity_type' => 'product',
        'bundle' => 'product',
        'data' => 'a:0:{};',
        'deleted' => '0',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'profile_id' => '1',
        'revision_id' => '1',
        'type' => 'billing',
        'uid' => '3',
        'status' => '1',
        'created' => '1493287440',
        'changed' => '1493287440',
        'data' => NULL,
      ],
    ];

    // Test with commerce_addressbook_default table.
    $tests[1] = $tests[0];
    $tests[1]['source_data']['commerce_addressbook_defaults'] = [
      [
        'cad_id' => '1',
        'profile_id' => '1',
        'type' => 'billing',
        'uid' => '3',
      ],
    ];

    // The expected results.
    $tests[1]['expected_data'][0]['cad_type'] = 'billing';

    return $tests;
  }

}
