<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;

/**
 * Tests product variation type migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProfileTypeTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_store'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('profile');
    $this->executeMigration('commerce1_profile_type');
  }

  /**
   * Test profile type migration from Drupal 7 to 8.
   *
   * Product variation types in Drupal 8 are product types in Drupal 7.
   */
  public function testProfileType() {
    $this->assertProfileType('billing', 'Billing', FALSE, FALSE);
    $this->assertProfileType('shipping', 'Shipping', FALSE, FALSE);
  }

}
