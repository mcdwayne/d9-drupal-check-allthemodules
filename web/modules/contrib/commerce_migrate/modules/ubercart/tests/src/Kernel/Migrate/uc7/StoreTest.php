<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

use Drupal\commerce_store\Entity\Store;
use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests store migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class StoreTest extends Ubercart7TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
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
  }

  /**
   * Test store migration.
   */
  public function testStore() {
    $this->assertStoreEntity(1, "Quark's", 'quark@example.com', 'USD', 'online', '1');

    $store = Store::load(1);
    $address = $store->getAddress();
    $this->assertAddressItem($address, 'CA', NULL, 'Deep Space 9', NULL, '9999', NULL, '47 The Promenade', 'Lower Level', NULL, NULL, NULL, NULL);
  }

}
