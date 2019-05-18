<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests payment gateway migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class PaymentGatewayTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_payment',
    'commerce_store',
    'profile',
    'state_machine',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('uc_payment_gateway');
  }

  /**
   * Tests payment gateway migration.
   */
  public function testPaymentGateway() {
    $this->assertPaymentGatewayEntity('cod', 'COD', NULL);
  }

}
