<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart 6 billing profile source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ProfileBilling
 * @group commerce_migrate_uc
 */
class ProfileTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_ubercart'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['uc_orders'] =
      [
        [
          'order_id' => '1',
          'uid' => '2',
          'order_status' => 'payment_received',
          'order_total' => '22.99000',
          'product_count' => '2',
          'primary_email' => 'f.bar@example.com',
          'delivery_first_name' => 'Wile E.',
          'delivery_last_name' => 'Coyote',
          'delivery_phone' => '',
          'delivery_company' => '',
          'delivery_street1' => '',
          'delivery_street2' => '',
          'delivery_city' => '',
          'delivery_zone' => '',
          'delivery_postal_code' => '',
          'delivery_country' => '',
          'billing_first_name' => 'Wile E.',
          'billing_last_name' => 'Coyote',
          'billing_phone' => '123-4567',
          'billing_company' => 'Acme',
          'billing_street1' => '1 Coyote Way',
          'billing_street2' => 'Utah',
          'billing_city' => 'Salt Lake',
          'billing_zone' => '58',
          'billing_postal_code' => '11111',
          'billing_country' => '840',
          'payment_method' => 'cod',
          'data' => 'a:0:{}',
          'created' => '1492868907',
          'modified' => '1498620003',
          'host' => '192.168.0.2',
          'currency' => 'USD',
        ],
        [
          'order_id' => '21',
          'uid' => '2',
          'order_status' => 'payment_received',
          'order_total' => '22.99000',
          'product_count' => '2',
          'primary_email' => 'f.bar@example.com',
          'delivery_first_name' => 'Wile E.',
          'delivery_last_name' => 'Coyote',
          'delivery_phone' => '',
          'delivery_company' => '',
          'delivery_street1' => '',
          'delivery_street2' => '',
          'delivery_city' => '',
          'delivery_zone' => '',
          'delivery_postal_code' => '',
          'delivery_country' => '',
          'billing_first_name' => 'Wile E.',
          'billing_last_name' => 'Coyote',
          'billing_phone' => '555-4567',
          'billing_company' => 'Acme',
          'billing_street1' => '1 Coyote Way',
          'billing_street2' => 'Utah',
          'billing_city' => 'Salt Lake',
          'billing_zone' => '58',
          'billing_postal_code' => '11111',
          'billing_country' => '840',
          'payment_method' => 'cod',
          'data' => 'a:1:{s:13:"complete_sale";s:9:"logged_in";}',
          'created' => '1492868908',
          'modified' => '1498630003',
          'host' => '192.168.0.2',
          'currency' => 'USD',
        ],
      ];
    $tests[0]['source_data']['uc_countries'] =
      [
        [
          'country_id' => '124',
          'country_name' => 'Canada',
          'country_iso_code_2' => 'CA',
          'country_iso_code_3' => 'CAN',
          'version' => 2,
          'weight' => 0,
        ],
        [
          'country_id' => '840',
          'country_name' => 'United States',
          'country_iso_code_2' => 'US',
          'country_iso_code_3' => 'USA',
          'version' => 1,
          'weight' => 0,
        ],
      ];
    $tests[0]['source_data']['uc_zones'] =
      [
        [
          'zone_id' => '58',
          'zone_country_country_id' => '840',
          'zone_code' => 'UT',
          'zone_name' => 'Utah',
        ],
      ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'order_id' => '1',
        'uid' => '2',
        'order_status' => 'payment_received',
        'order_total' => '22.99000',
        'product_count' => '2',
        'primary_email' => 'f.bar@example.com',
        'delivery_first_name' => 'Wile E.',
        'delivery_last_name' => 'Coyote',
        'delivery_phone' => '',
        'delivery_company' => '',
        'delivery_street1' => '',
        'delivery_street2' => '',
        'delivery_city' => '',
        'delivery_zone' => '',
        'delivery_postal_code' => '',
        'delivery_country' => '',
        'billing_first_name' => 'Wile E.',
        'billing_last_name' => 'Coyote',
        'billing_phone' => '123-4567',
        'billing_company' => 'Acme',
        'billing_street1' => '1 Coyote Way',
        'billing_street2' => 'Utah',
        'billing_city' => 'Salt Lake',
        'billing_zone' => '58',
        'billing_postal_code' => '11111',
        'billing_country' => '840',
        'payment_method' => 'cod',
        'data' => [],
        'created' => '1492868907',
        'modified' => '1498620003',
        'host' => '192.168.0.2',
        'currency' => 'USD',
        'zone_code' => 'UT',
        'country_iso_code_2' => 'US',
        'status' => '0',
        'is_default' => '',
      ],
      [
        'order_id' => '21',
        'uid' => '2',
        'order_status' => 'payment_received',
        'order_total' => '22.99000',
        'product_count' => '2',
        'primary_email' => 'f.bar@example.com',
        'delivery_first_name' => 'Wile E.',
        'delivery_last_name' => 'Coyote',
        'delivery_phone' => '',
        'delivery_company' => '',
        'delivery_street1' => '',
        'delivery_street2' => '',
        'delivery_city' => '',
        'delivery_zone' => '',
        'delivery_postal_code' => '',
        'delivery_country' => '',
        'billing_first_name' => 'Wile E.',
        'billing_last_name' => 'Coyote',
        'billing_phone' => '555-4567',
        'billing_company' => 'Acme',
        'billing_street1' => '1 Coyote Way',
        'billing_street2' => 'Utah',
        'billing_city' => 'Salt Lake',
        'billing_zone' => '58',
        'billing_postal_code' => '11111',
        'billing_country' => '840',
        'payment_method' => 'cod',
        'data' => unserialize('a:1:{s:13:"complete_sale";s:9:"logged_in";}'),
        'created' => '1492868908',
        'modified' => '1498630003',
        'host' => '192.168.0.2',
        'currency' => 'USD',
        'zone_code' => 'UT',
        'country_iso_code_2' => 'US',
        'status' => '1',
        'is_default' => '1',
      ],
    ];

    return $tests;
  }

}
