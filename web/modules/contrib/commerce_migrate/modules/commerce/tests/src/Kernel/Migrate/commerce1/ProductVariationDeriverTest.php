<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

/**
 * Test Product Variation Deriver.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce
 */
class ProductVariationDeriverTest extends Commerce1TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_store',
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
   * Test product variation migrations.
   */
  public function testProductVariationMigrations() {
    // Create the product variation derivatives.
    $migrations = $this->pluginManager->createInstances(['commerce1_product_variation']);

    // Test that the variation for drinks exists.
    $this->assertArrayHasKey('commerce1_product_variation:drinks', $migrations, "Commerce product variation migrations exist after commerce_product installed");

    // Test that the fields for shoes exist in the show migration.
    /** @var \Drupal\migrate\Plugin\migration $migration */
    $migration = $migrations['commerce1_product_variation:shoes'];
    $process = $migration->getProcess();
    $this->assertArrayHasKey('field_employee_price_shoes', $process, "Commerce product variation shoes has employee_price_shoes field.");
    $migration = $migrations['commerce1_product_variation:drinks'];
    $process = $migration->getProcess();
    $this->assertArrayHasKey('field_employee_price', $process, "Commerce product variation drinks has employee_price field.");
  }

}
