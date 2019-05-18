<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests currency migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class CurrencyTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_price'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('uc_currency');
  }

  /**
   * Test currency migration.
   */
  public function testCurrency() {
    $this->assertCurrencyEntity('USD', 'USD', 'US Dollar', '840', 2, '$');
  }

}
