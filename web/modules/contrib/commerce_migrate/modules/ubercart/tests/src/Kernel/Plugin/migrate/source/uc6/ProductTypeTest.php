<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart product type source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6\ProductType
 * @group commerce_migrate
 * @group commerce_migrate_uc6
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
        'module' => 'node',
        'description' => 'A page',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'page',
      ],
      [
        'type' => 'product',
        'name' => 'Product',
        'module' => 'uc_product',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'product',
      ],
      [
        'type' => 'product_kit',
        'name' => 'Product Kit',
        'module' => 'uc_product_kit',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'product_kit',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'type' => 'product',
        'name' => 'Product',
        'module' => 'uc_product',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'product',
      ],
      [
        'type' => 'product_kit',
        'name' => 'Product Kit',
        'module' => 'uc_product_kit',
        'description' => '',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'product_kit',
      ],
    ];

    return $tests;
  }

}
