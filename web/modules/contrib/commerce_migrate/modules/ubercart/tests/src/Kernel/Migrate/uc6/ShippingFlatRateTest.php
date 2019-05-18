<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests flat rate shipping migration from Uberart 6.
 *
 * @requires module commerce_shipping
 * @requires module physical
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ShippingFlatRateTest extends Ubercart6TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'commerce_shipping',
    'commerce_store',
    'path',
    'profile',
    'physical',
    'state_machine',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_shipping_method');
    $this->executeMigration('uc_shipping_flat_rate');
  }

  /**
   * Test flat rate shipping method migration.
   */
  public function testShippingFlatRate() {
    $type = [
      'id' => '1',
      'label' => 'Fluff Ltd',
      'rate_amount' =>
        [
          'number' => '5.00',
          'currency_code' => 'USD',
        ],
      'stores' => ['1'],
    ];
    $this->assertShippingMethod($type);
    $type = [
      'id' => '2',
      'label' => 'Joopleberry Co.',
      'rate_amount' =>
        [
          'number' => '2.50',
          'currency_code' => 'USD',
        ],
      'stores' => ['1'],
    ];
    $this->assertShippingMethod($type);
  }

}
