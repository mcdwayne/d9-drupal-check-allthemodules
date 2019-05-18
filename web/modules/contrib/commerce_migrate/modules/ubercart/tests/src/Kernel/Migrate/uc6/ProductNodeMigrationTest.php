<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Migrate\uc6;

/**
 * Tests that migrations for product nodes do not exist.
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductNodeMigrationTest extends Ubercart6TestBase {

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
   * Test node product type migrations are not created.
   */
  public function testNoProducts() {
    /** @var \Drupal\Migrate\Plugin\Migration $migration */
    $migrations = $this->pluginManager->createInstances(['d6_node']);
    $name = 'd6_node:page';
    $this->assertArrayHasKey($name, $migrations, 'Node type page migration exists.');
    $migration = $migrations[$name];
    $destination = $migration->getDestinationConfiguration();
    $this->assertSame('entity:node', $destination['plugin']);

    $name = 'd6_node:story';
    $this->assertArrayHasKey($name, $migrations, 'Node type story migration exists.');
    $migration = $migrations[$name];
    $destination = $migration->getDestinationConfiguration();
    $this->assertSame('entity:node', $destination['plugin']);

    $name = 'd6_node:product';
    $this->assertArrayHasKey($name, $migrations, 'Product type product migration exists.');
    $migration = $migrations[$name];
    $destination = $migration->getDestinationConfiguration();
    $this->assertSame('entity:commerce_product', $destination['plugin']);

    $this->enableModules(['language', 'content_translation']);
    $migrations = $this->pluginManager->createInstances(['d6_node_translation']);
    // Translations are not enabled in the ubercart 6 test fixture.
    $this->assertArrayNotHasKey('d6_node_translation:page', $migrations, 'Node translation migration for page exists.');
    $this->assertArrayNotHasKey('d6_node_translation:story', $migrations, 'Node translation migration for story exists.');
    $this->assertArrayNotHasKey('d6_node_translation:product', $migrations, 'Node translation migration for product  exists.');
  }

}
