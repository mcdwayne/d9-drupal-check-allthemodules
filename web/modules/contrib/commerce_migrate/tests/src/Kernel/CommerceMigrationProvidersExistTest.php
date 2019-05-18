<?php

namespace Drupal\Tests\commerce_migrate\Kernel;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests that modules exist for all source and destination plugins.
 *
 * @group commerce_migrate
 */
class CommerceMigrationProvidersExistTest extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_store',
    'commerce_migrate',
    'commerce_migrate_commerce',
    'commerce_migrate_ubercart',
  ];

  /**
   * Migration plugin tags to create instances for.
   *
   * @var array
   */
  protected $tags = [
    'Ubercart',
    'Commerce',
  ];

  /**
   * Invalid migration destinations.
   *
   * @var array
   */
  protected $invalidDestinations = [
    'migrate',
    'migrate_drupal',
    'migrate_drupal_ui',
    'commerce_migrate',
    'commerce_migrate_commerce',
    'commerce_migrate_ubercart',
  ];

  /**
   * Tests that modules exist for all source and destination plugins.
   */
  public function testProvidersExist() {
    // Enable all modules.
    self::$modules = array_keys($this->coreModuleListDataProvider());
    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');
    // Get all the commerce_migrate migrations.
    $migrations = [];
    foreach ($this->tags as $tag) {
      $migrations = array_merge($migrations, $plugin_manager->createInstancesByTag($tag));
    }

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $migration) {
      $source_module = $migration->getSourcePlugin()->getSourceModule();
      $destination_module = $migration->getDestinationPlugin()->getDestinationModule();
      $migration_id = $migration->getPluginId();
      $this->assertTrue($source_module, sprintf('Source module not found for %s.', $migration_id));
      $this->assertTrue($destination_module, sprintf('Destination module not found for %s.', $migration_id));
      // Destination module can't be a migrate module.
      $this->assertNotContains($destination_module, $this->invalidDestinations, sprintf('Invalid destination for %s.', $migration_id));
    }
  }

}
