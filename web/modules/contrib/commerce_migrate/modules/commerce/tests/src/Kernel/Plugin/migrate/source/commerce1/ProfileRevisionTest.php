<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

/**
 * Tests the profile revision source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\ProfileRevision
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProfileRevisionTest extends ProfileTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_commerce'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = parent::providerSource();

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'profile_id' => '2',
        'revision_id' => '3',
        'type' => 'shipping',
        'uid' => '5',
        'status' => '1',
        'created' => '1493287441',
        'changed' => '1493287551',
        'data' => unserialize('a:1:{s:4:"ship";s:9:"andromeda";}'),
        'cad_type' => 'shipping',
        'commerce_customer_address' => [
          [
            'country' => 'US',
            'administrative_area' => 'CA',
          ],
        ],
        'revision_uid' => '1',
        'log' => '',
        'revision_timestamp' => '1493287451',
        'revision_status' => '9',
        'revision_data' => unserialize('a:1:{s:4:"ship";s:7:"defiant";}'),
      ],
    ];
    $tests[0]['expected_count'] = NULL;
    $tests[0]['configuration']['profile_type'] =
      [
        'billing',
        'shipping',
      ];

    // Repeat test0 without the commerce_addresbook_defaults table.
    $tests[1] = $tests[0];
    unset($tests[1]['source_data']['commerce_addressbook_defaults']);
    $tests[1]['expected_data'][0]['cad_type'] = NULL;
    $tests[1]['expected_data'][0]['revision_timestamp'] = '1493287451';
    $tests[1]['expected_data'][0]['log'] = NULL;
    unset($tests[1]['expected_data'][1]);

    // Repeat test0 configuration profile_type as a string.
    $tests[2] = $tests[0];
    // The expected results.
    $tests[2]['expected_data'] = [
      [
        'profile_id' => '2',
        'revision_id' => '3',
        'type' => 'shipping',
        'uid' => '5',
        'status' => '1',
        'created' => '1493287441',
        'changed' => '1493287551',
        'data' => unserialize('a:1:{s:4:"ship";s:9:"andromeda";}'),
        'cad_type' => 'shipping',
        'commerce_customer_address' => [
          [
            'country' => 'US',
            'administrative_area' => 'CA',
          ],
        ],
        'revision_uid' => '1',
        'log' => '',
        'revision_timestamp' => '1493287451',
        'revision_status' => '9',
        'revision_data' => unserialize('a:1:{s:4:"ship";s:7:"defiant";}'),
      ],
    ];
    $tests[2]['configuration']['profile_type'] = 'shipping';
    return $tests;
  }

}
