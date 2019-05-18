<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\Migration;

/**
 * Tests that a source plugin is created when there is no fallback connection.
 *
 * In a scenario where only the source and destination plugins from
 * commerce_migrate are needed and commerce_migrate is enabled the fallback
 * database connection, 'migrate' is not available, but it is needed by
 * functions in the hook migrations_plugin_alter. Specifically, any source
 * plugin that is of class Node and has a configuration key of 'node_type' will
 * cause the hook to attempt to connect to the source db to determine the node
 * types that are product types. This test ensures that a migration with such
 * a plugin will be created.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class PluginsAlterConnectionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate',
    'migrate_drupal',
    'commerce_migrate',
    'commerce_migrate_ubercart',
    'plugins_alter_connection_test',
    'node',
  ];

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
   * Tests that a requirements exception when no connection can be made.
   */
  public function testConfigurationMerge() {
    $this->assertInstanceOf(Migration::class, $this->pluginManager->createInstance('connection_test'));
  }

}
