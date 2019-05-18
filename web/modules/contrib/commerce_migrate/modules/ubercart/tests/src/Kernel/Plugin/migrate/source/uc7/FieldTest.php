<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart 7 field source plugin.
 *
 * The data blobs in the source tables are deliberately set to an empty array
 * so that it is easier to see the additional rows and the entity_type values
 * set in the source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7\Field
 * @group commerce_migrate_uc7
 */
class FieldTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate_ubercart',
    'field',
    'migrate_drupal',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [
      [
        'source_data' => [],
        'expected_data' => [],
      ],
    ];

    // The source data. Tests with a field, test_field, on a non product node
    // and a product node, a field only on a non product nodes and a field only
    // on a product node.
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
        'description' => 'product',
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
        'description' => 'A product group',
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

    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '1',
        'field_name' => 'field_test',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0:{}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
      [
        'id' => '2',
        'field_name' => 'field_image',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0:{}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
      [
        'id' => '3',
        'field_name' => 'field_picture',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0:{}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];

    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '33',
        'field_id' => '1',
        'field_name' => 'field_test',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '21',
        'field_id' => '1',
        'field_name' => 'field_test',
        'entity_type' => 'node',
        'bundle' => 'product_kit',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '22',
        'field_id' => '2',
        'field_name' => 'field_image',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '23',
        'field_id' => '3',
        'field_name' => 'field_picture',
        'entity_type' => 'node',
        'bundle' => 'product_kit',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'id' => '1',
        'field_name' => 'field_test',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0:{}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
        'entity_type' => 'commerce_product',
      ],
      [
        'id' => '1',
        'field_name' => 'field_test',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0:{}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
        'entity_type' => 'node',
      ],
      [
        'id' => '2',
        'field_name' => 'field_image',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0:{}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
        'entity_type' => 'node',
      ],
      [
        'id' => '3',
        'field_name' => 'field_picture',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0:{}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
        'entity_type' => 'commerce_product',
      ],
    ];

    // Initialize iterator adds a row, one more than the query count.
    $tests[0]['expected_count'] = 3;
    return $tests;
  }

}
