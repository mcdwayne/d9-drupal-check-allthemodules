<?php

namespace Drupal\Tests\commerce_migrate_magento\Kernel\Plugin\migrate;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\commerce_migrate\Kernel\Plugin\migrate\DestinationCategoryTestTrait;

/**
 * Tests that all migrations are tagged as either content or configuration.
 *
 * @requires module migrate_plus
 *
 * @group commerce_migrate
 * @group commerce_migrate_magento2
 */
class DestinationCategoryTest extends KernelTestBase {

  use DestinationCategoryTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate',
    'commerce_migrate_magento',
    'commerce_product',
    'file',
    'image',
    'migrate',
    'migrate_source_csv',
    'profile',
    'taxonomy',
    'user',
  ];

  /**
   * Tests Commerce 1 migrations are tagged as either Configuration or Content.
   */
  public function testMagento2Categories() {
    $migrations = \Drupal::service('plugin.manager.migration')->createInstancesByTag('Magento 2');
    $this->assertArrayHasKey('magento2_product_variation', $migrations);
    $this->assertCategories($migrations);
  }

}
