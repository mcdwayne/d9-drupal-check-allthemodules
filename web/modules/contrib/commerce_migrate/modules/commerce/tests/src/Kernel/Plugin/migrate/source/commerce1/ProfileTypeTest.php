<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 profile type source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\ProfileType
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProfileTypeTest extends MigrateSqlSourceTestBase {

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
        'type' => 'customer',
        'uid' => '12',
        'status' => '1',
        'created' => '',
        'changed' => '1',
        'data' => NULL,
      ],
      [
        'profile_id' => '11',
        'revision_id' => '11',
        'type' => 'member',
        'uid' => '12',
        'status' => '1',
        'created' => '',
        'changed' => '1',
        'data' => NULL,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      ['type' => 'customer'],
      ['type' => 'member'],
    ];

    return $tests;
  }

}
