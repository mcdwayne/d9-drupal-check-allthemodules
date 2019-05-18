<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc7;

/**
 * Tests that migrations for product nodes do not exist.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ProductNodeMigrationTest extends Ubercart7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

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
   * Test node product type migrations are created.
   */
  public function testNoProducts() {
    /** @var \Drupal\Migrate\Plugin\Migration $migration */$migrations = $this->pluginManager->createInstances(['d7_node']);
    $name = 'd7_node:page';
    $this->assertArrayHasKey($name, $migrations, 'Node type page migration exists.');
    $migration = $migrations[$name];
    $destination = $migration->getDestinationConfiguration();
    $this->assertSame('entity:node', $destination['plugin']);

    $name = 'd7_node:article';
    $this->assertArrayHasKey($name, $migrations, 'Node type article migration exists.');
    $migration = $migrations[$name];
    $destination = $migration->getDestinationConfiguration();
    $this->assertSame('entity:node', $destination['plugin']);

    // The destination is altered for nodes that are products.
    $name = 'd7_node:product';
    $this->assertArrayHasKey($name, $migrations, 'Product type product migration exists.');
    $migration = $migrations[$name];
    $destination = $migration->getDestinationConfiguration();
    $this->assertSame('entity:commerce_product', $destination['plugin']);

    $this->enableModules(['language', 'content_translation']);
    $migrations = $this->pluginManager->createInstances(['d7_node_translation']);
    // Translations are not enabled in the ubercart 7 test fixture.
    $this->assertArrayNotHasKey('d7_node_translation:page', $migrations, 'Node translation migration for page exists.');
    $this->assertArrayNotHasKey('d7_node_translation:article', $migrations, 'Node translation migration for article exists.');
    $this->assertArrayNotHasKey('d7_node_translation:product', $migrations, 'Node translation migration for product  exists.');
  }

}
