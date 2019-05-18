<?php

namespace Drupal\Tests\commerce_migrate\Kernel;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests that labels exist for all migrations.
 *
 * @group commerce_migrate
 */
class CommerceMigrationLabelExistTest extends MigrateDrupalTestBase {

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
   * Tests that labels exist for all migrations.
   */
  public function testLabelExist() {
    // Install all available modules.
    $module_handler = $this->container->get('module_handler');
    $modules = $this->coreModuleListDataProvider();
    $modules_enabled = $module_handler->getModuleList();
    $modules_to_enable = array_keys(array_diff_key($modules, $modules_enabled));
    $this->enableModules($modules_to_enable);

    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $plugin_manager */
    $plugin_manager = $this->container->get('plugin.manager.migration');
    // Get all the commerce_migrate migrations.
    $migrations = [];
    foreach ($this->tags as $tag) {
      $migrations = array_merge($migrations, $plugin_manager->createInstancesByTag($tag));
    }

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    foreach ($migrations as $migration) {
      $migration_id = $migration->getPluginId();
      $this->assertNotEmpty($migration->label(), 'Label not found for ' . $migration_id);
    }
  }

}
