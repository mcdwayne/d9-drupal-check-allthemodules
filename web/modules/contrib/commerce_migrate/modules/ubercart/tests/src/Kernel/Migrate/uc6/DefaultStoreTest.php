<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests default store migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class DefaultStoreTest extends Ubercart6TestBase {

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
    $this->executeMigration('uc6_default_store');
  }

  /**
   * Test default store migration from Ubercart 6 to Commerce 2.
   */
  public function testMigrateDefaultStore() {
    $this->assertDefaultStore();
  }

}
