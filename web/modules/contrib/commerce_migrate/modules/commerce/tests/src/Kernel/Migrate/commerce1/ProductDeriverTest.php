<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

/**
 * Test Product Variation Deriver.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce
 */
class ProductDeriverTest extends Commerce1TestBase {

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
   * Test product variation migrations with commerce_product enabled.
   */
  public function testProductMigrations() {
    // Create the product variation derivatives.
    $migrations = $this->pluginManager->createInstances(['commerce1_product']);

    // Test that the variation for drinks exists.
    $this->assertArrayHasKey('commerce1_product:drinks', $migrations, "Commerce product migrations exist after commerce_product installed");

    // Test that the fields for bags & cases exist in the show migration.
    /** @var \Drupal\migrate\Plugin\migration $migration */
    $migration = $migrations['commerce1_product:bags_cases'];
    $process = $migration->getProcess();
    $this->assertArrayHasKey('field_collection', $process, "Commerce product bags and cases has collection field.");
    $this->assertArrayHasKey('field_category', $process, "Commerce product bags and cases has category field.");
    $this->assertArrayHasKey('field_gender', $process, "Commerce product bags and cases has gender field.");
    $this->assertArrayHasKey('field_brand', $process, "Commerce product bags and cases has brand field.");
  }

}
