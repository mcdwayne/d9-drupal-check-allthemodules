<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart product type source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7\ProductType
 *
 * @group commerce_migrate_uc7
 */
class ProductTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate_ubercart',
    'migrate_drupal',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node_type'] = [
      [
        'type' => 'page',
        'name' => 'Page',
        'base' => 'node_content',
        'module' => 'node',
        'description' => 'A page',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'page',
      ],
      [
        'type' => 'product',
        'name' => 'Product',
        'base' => 'uc_product',
        'module' => 'uc_product',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'product',
      ],
      [
        'type' => 'product_kit',
        'name' => 'Product Kit',
        'base' => 'uc_product',
        'module' => 'uc_product_kit',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'product_kit',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'type' => 'product',
        'name' => 'Product',
        'base' => 'uc_product',
        'module' => 'uc_product',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'product',
      ],
      [
        'type' => 'product_kit',
        'name' => 'Product Kit',
        'base' => 'uc_product',
        'module' => 'uc_product_kit',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'product_kit',
      ],
    ];

    return $tests;
  }

}
