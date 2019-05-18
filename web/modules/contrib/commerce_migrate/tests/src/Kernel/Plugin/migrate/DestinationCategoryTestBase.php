<?php

namespace Drupal\Tests\commerce_migrate\Kernel\Plugin\migrate;

use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\Tests\migrate_drupal\Traits\CreateMigrationsTrait;

/**
 * Tests that all migrations are tagged as either content or configuration.
 *
 * @group commerce_migrate
 */
abstract class DestinationCategoryTestBase extends MigrateDrupalTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;
  use CreateMigrationsTrait;
  use DestinationCategoryTestTrait;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
    $this->moduleHandler = \Drupal::service('module_handler');
  }

}
