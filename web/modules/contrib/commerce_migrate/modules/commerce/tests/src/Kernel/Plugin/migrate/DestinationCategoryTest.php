<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate;

use Drupal\Tests\commerce_migrate\Kernel\Plugin\migrate\DestinationCategoryTestBase;

/**
 * Tests that all migrations are tagged as either content or configuration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class DestinationCategoryTest extends DestinationCategoryTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'action',
    'address',
    'commerce',
    'commerce_log',
    'commerce_migrate',
    'commerce_migrate_commerce',
    'commerce_order',
    'commerce_payment',
    'commerce_price',
    'commerce_product',
    'commerce_shipping',
    'commerce_store',
    'commerce_tax',
    'datetime',
    'entity',
    'entity_reference_revisions',
    'filter',
    'image',
    'inline_entity_form',
    'link',
    'node',
    'options',
    'path',
    'profile',
    'physical',
    'state_machine',
    'taxonomy',
    'telephone',
    'text',
    'views',
  ];

  /**
   * Tests Commerce 1 migrations are tagged as either Configuration or Content.
   */
  public function testCommerceCategories() {
    $dirs = $this->moduleHandler->getModuleDirectories();
    $commerce_migrate_commerce_directory = $dirs['commerce_migrate_commerce'];
    $this->loadFixture("$commerce_migrate_commerce_directory/tests/fixtures/ck2.php");
    $migrations = $this->migrationPluginManager->createInstancesByTag('Commerce 1');
    $this->assertArrayHasKey('commerce1_product:tops', $migrations);
    $this->assertCategories($migrations);
  }

}
