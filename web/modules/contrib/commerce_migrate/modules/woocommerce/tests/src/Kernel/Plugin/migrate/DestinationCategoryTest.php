<?php

namespace Drupal\Tests\commerce_migrate_woocommerce\Kernel\Plugin\migrate;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\commerce_migrate\Kernel\Plugin\migrate\DestinationCategoryTestTrait;

/**
 * Tests that all migrations are tagged as either content or configuration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_woocommerce
 */
class DestinationCategoryTest extends KernelTestBase {

  use DestinationCategoryTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate',
    'commerce_migrate_woocommerce',
    'commerce_product',
    'image',
    'migrate',
    'migrate_source_csv',
    'taxonomy',
  ];

  /**
   * Tests Commerce 1 migrations are tagged as either Configuration or Content.
   */
  public function testWoo3Categories() {
    $migrations = \Drupal::service('plugin.manager.migration')->createInstancesByTag('Magento 2');
    $this->assertArrayHasKey('woo3_taxonomy_vocabulary', $migrations);
    $this->assertCategories($migrations);
  }

}
