<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests default store migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class DefaultStoreTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_store',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrateStore();
    $this->executeMigration('uc7_default_store');
  }

  /**
   * Test default store migration from Ubercart 7 to Commerce 2.
   */
  public function testMigrateDefaultStore() {
    $this->assertDefaultStore();
  }

}
