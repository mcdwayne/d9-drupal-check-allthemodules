<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Ubercart store source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\Store
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class StoreTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['uc_countries'] = [
      [
        'country_id' => '124',
        'country_name' => 'Canada',
        'country_iso_code_2' => 'CA',
        'country_iso_code_3' => 'CAN',
        'version' => '2',
        'weight' => '0',
      ],
    ];
    $tests[0]['source_data']['users'] = [
      [
        'uid' => '2',
        'name' => 'Owner',
        'pass' => '$S',
        'mail' => 'odo@local.host',
        'theme' => '',
        'signature' => '',
        'signature_format' => 'filtered_html',
        'created' => '1432750741',
        'access' => '0',
        'login' => '0',
        'status' => '1',
        'timezone' => 'America/Chicago',
        'language' => '',
        'picture' => '0',
        'init' => 'odo@local.host',
        'data' => 'a:1:{s:7:"contact";i:1;}',
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'uc_store_name',
        'value' => serialize('Name'),
      ],
      [
        'name' => 'uc_store_owner',
        'value' => serialize('Owner'),
      ],
      [
        'name' => 'uc_currency_code',
        'value' => serialize('1234'),
      ],
      [
        'name' => 'uc_store_street1',
        'value' => serialize('street 1'),
      ],
      [
        'name' => 'uc_store_street2',
        'value' => serialize('street 2'),
      ],
      [
        'name' => 'uc_store_zone',
        'value' => serialize('zone'),
      ],
      [
        'name' => 'uc_store_city',
        'value' => serialize('city'),
      ],
      [
        'name' => 'uc_store_postal_code',
        'value' => serialize('postal code'),
      ],
      [
        'name' => 'uc_store_country',
        'value' => serialize('124'),
      ],
      [
        'name' => 'uc_store_phone',
        'value' => serialize('555-5555'),
      ],
      [
        'name' => 'uc_store_fax',
        'value' => serialize('444-4444'),
      ],
      [
        'name' => 'uc_store_email',
        'value' => serialize('store@example.com'),
      ],
      [
        'name' => 'uc_store_email_include_name',
        'value' => serialize('include name'),
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'uc_store_name' => 'Name',
        'uc_store_owner' => 'Owner',
        'uc_currency_code' => '1234',
        'uc_store_street1' => 'street 1',
        'uc_store_street2' => 'street 2',
        'uc_store_zone' => 'zone',
        'uc_store_city' => 'city',
        'uc_store_postal_code' => 'postal code',
        'uc_store_country' => '124',
        'uc_store_phone' => '555-5555',
        'uc_store_fax' => '444-4444',
        'uc_store_email' => 'store@example.com',
        'uc_store_email_include_name' => 'include name',
        'uid' => '2',
        'country_iso_code_2' => 'CA',
      ],
    ];
    $tests[0]['expected_count'] = NULL;
    $tests[0]['configuration'] = [
      'variables' =>
        [
          'uc_store_name',
          'uc_store_owner',
          'uc_currency_code',
          'uc_store_street1',
          'uc_store_street2',
          'uc_store_zone',
          'uc_store_city',
          'uc_store_postal_code',
          'uc_store_country',
          'uc_store_phone',
          'uc_store_fax',
          'uc_store_email',
          'uc_store_email_include_name',
        ],
    ];
    return $tests;
  }

}
