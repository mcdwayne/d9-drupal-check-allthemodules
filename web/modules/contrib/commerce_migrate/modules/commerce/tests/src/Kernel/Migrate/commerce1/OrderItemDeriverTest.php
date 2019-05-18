<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

/**
 * Test Order item deriver.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce
 */
class OrderItemDeriverTest extends Commerce1TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_price',
    'commerce_store',
    'commerce_product',
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
   * Test product variation migrations with commerce_product enabled.
   */
  public function testOrderItemMigration() {
    // Create the order item derivatives.
    $migrations = $this->pluginManager->createInstances(['commerce1_order_item']);

    // Test that the line item for product exists.
    $this->assertArrayHasKey('commerce1_order_item:product', $migrations, "Commerce product migrations exist after commerce_product installed");

    // Test that the product line item price fields exist in the migration.
    /** @var \Drupal\migrate\Plugin\migration $migration */
    $migration = $migrations['commerce1_order_item:product'];
    $process = $migration->getProcess();
    $this->assertArrayHasKey('unit_price', $process, "Commerce order does not have a unit price field.");
    $this->assertArrayHasKey('total_price', $process, "Commerce order does not have a total price field.");

    // Test that the line item for shipping does not exist.
    $this->assertArrayNotHasKey('commerce1_order_item:shipping', $migrations, "Commerce product migrations exist after commerce_product installed");

  }

}
