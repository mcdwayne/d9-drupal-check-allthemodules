<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

/**
 * Tests profile deriver.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProfileDeriverTest extends Commerce1TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_store', 'profile'];

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pluginManager = $this->container->get('plugin.manager.migration');
  }

  /**
   * Test product variation migrations.
   */
  public function testProfileMigrations() {
    // Create the profile migration derivatives.
    $migrations = $this->pluginManager->createInstances(['commerce1_profile']);

    // Test that variations exist for billing and shipping and that they have
    // a process for the address.
    $profile_types = ['billing', 'shipping'];
    foreach ($profile_types as $type) {
      $derivative = "commerce1_profile:$type";
      $this->assertArrayHasKey($derivative, $migrations, "Commerce profile '$type' migrations do not exist after profile installed");

      /** @var \Drupal\migrate\Plugin\migration $migration */
      $migration = $migrations[$derivative];
      $process = $migration->getProcess();
      $this->assertArrayHasKey('address', $process, "Commerce profile '$type' does not have address.");
    }
  }

}
