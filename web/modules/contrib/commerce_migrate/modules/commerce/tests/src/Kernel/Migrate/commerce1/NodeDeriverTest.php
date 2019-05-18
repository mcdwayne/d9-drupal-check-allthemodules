<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

/**
 * Test Product Variation Deriver.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce
 */
class NodeDeriverTest extends Commerce1TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_store',
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
   * Test product node migrations for product displays do not exist.
   */
  public function testProductNodeMigrations() {
    // Create the product variation derivatives.
    $migrations = $this->pluginManager->createInstances(['d7_node']);

    // Test that node migrations for nodes exist.
    $nodes = [
      'ad_push',
      'blog_post',
      'page',
      'slideshow',
    ];
    foreach ($nodes as $node) {
      $this->assertArrayHasKey('d7_node:' . $node, $migrations, "Node migration page does not exist");
    }

    // Test that derived node migrations for product nodes do not exist.
    $products = [
      'bags_cases',
      'drinks',
      'hats',
      'products',
      'shoes',
      'storage_devices',
      'tops',
    ];
    foreach ($products as $product) {
      $this->assertArrayNotHasKey('d7_node:' . $product, $migrations, "Node migration $product exists");
    }
  }

}
