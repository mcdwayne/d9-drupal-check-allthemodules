<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 line item source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\ProductType
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProductTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_commerce'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['commerce_product_type'] = [
      [
        'type' => 'product',
        'name' => 'Product',
        'description' => 'Basic product type',
        'help' => '',
        'revision' => '1',
      ],
      [
        'type' => 'bag',
        'name' => 'Bag',
        'description' => 'Shoulder bag',
        'help' => '',
        'revision' => '1',
      ],
    ];
    // The source data.
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'field_x',
        'entity_type' => 'commerce_product',
        'bundle' => 'bag',
        'deleted' => '0',
        'data' => serialize([
          'display' => [
            'default' => [
              'settings' => [
                'line_item_type' => 'product',
              ],
            ],
          ],
        ]),
      ],
      [
        'id' => '3',
        'field_id' => '22',
        'field_name' => 'field_y',
        'entity_type' => 'commerce_product',
        'bundle' => 'product',
        'deleted' => '0',
        'data' => serialize('no_line_item_type'),
      ],
      [
        'id' => '4',
        'field_id' => '12',
        'field_name' => 'field_z',
        'entity_type' => 'commerce_product',
        'bundle' => 'product',
        'deleted' => '0',
        'data' => serialize('123'),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'type' => 'product',
        'name' => 'Product',
        'description' => 'Basic product type',
        'help' => '',
        'revision' => '1',
        'line_item_type' => '',
      ],
      [
        'type' => 'bag',
        'name' => 'Bag',
        'description' => 'Shoulder bag',
        'help' => '',
        'revision' => '1',
        'line_item_type' => 'product',
      ],
    ];
    $tests[0]['expected_count'] = 2;

    return $tests;
  }

}
