<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart 7 product source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7\Product
 * @group commerce_migrate
 * @group commerce_migrate_uc7
 */
class ProductTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_migrate_ubercart',
    'migrate_drupal',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'type' => 'page',
        'language' => 'en',
        'title' => 'node title 1',
        'uid' => 1,
        'status' => 1,
        'created' => 1279051598,
        'changed' => 1279051598,
        'comment' => 2,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'type' => 'product',
        'language' => 'en',
        'title' => 'product 2',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'type' => 'product',
        'language' => 'en',
        'title' => 'product 5',
        'uid' => 1,
        'status' => 1,
        'created' => 1279291908,
        'changed' => 1279309993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
      ],
    ];
    $tests[0]['source_data']['node_revision'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'uid' => 2,
        'title' => 'node title 1',
        'log' => '',
        'timestamp' => 1279051598,
        'status' => 1,
        'comment' => 2,
        'promote' => 1,
        'sticky' => 0,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'uid' => 2,
        'title' => 'product 2',
        'log' => '',
        'timestamp' => 1279308993,
        'status' => 1,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'uid' => 1,
        'title' => 'product 5',
        'log' => '',
        'timestamp' => 1279309993,
        'status' => 1,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,

      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '2',
        'translatable' => '0',
      ],
      [
        'id' => '3',
        'translatable' => '1',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '3',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'product',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '4',
        'field_id' => '3',
        'field_name' => 'title_field',
        'entity_type' => 'node',
        'bundle' => 'article',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_revision_body'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'Foobaz',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'product',
        'deleted' => '0',
        'entity_id' => '2',
        'revision_id' => '2',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'body 2',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'product',
        'deleted' => '0',
        'entity_id' => '6',
        'revision_id' => '6',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'body 6',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
    ];
    $tests[0]['source_data']['field_revision_title_field'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => '0',
        'entity_id' => '5',
        'revision_id' => '5',
        'language' => 'en',
        'delta' => '0',
        'title_field_value' => 'node title 5 (title_field)',
        'title_field_format' => NULL,
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => '0',
        'entity_id' => '6',
        'revision_id' => '6',
        'language' => 'en',
        'delta' => '0',
        'title_field_value' => 'node title 5 (title_field)',
        'title_field_format' => NULL,
      ],
    ];

    $tests[0]['source_data']['uc_products'] = [
      [
        'vid' => '2',
        'nid' => '2',
        'model' => 'Hat',
        'list_price' => '1.00',
        'cost' => '0.50',
        'sell_price' => '2.00',
        'weight' => 1,
        'weight_units' => 'gm',
        'length' => '20',
        'width' => '11',
        'height' => '10',
        'length_units' => 'cm',
        'pkg_qty' => '1',
        'default_qty' => '1',
        'unique_hash' => 'hash',
        'ordering' => 0,
        'shippable' => '1',
      ],
      [
        'vid' => '6',
        'nid' => '6',
        'model' => 'Ship',
        'list_price' => '1.00',
        'cost' => '0.50',
        'sell_price' => '3.00',
        'weight' => 1,
        'weight_units' => 'gm',
        'length' => '20',
        'width' => '11',
        'height' => '10',
        'length_units' => 'cm',
        'pkg_qty' => '1',
        'default_qty' => '1',
        'unique_hash' => 'hash',
        'ordering' => 0,
        'shippable' => '1',
      ],
    ];
    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'nid' => 2,
        'vid' => 2,
        'type' => 'product',
        'language' => 'en',
        'title' => 'product 2',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 2,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279308993,
        'body' => [
          [
            'value' => 'body 2',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
        'model' => 'Hat',
        'sell_price' => '2.00',
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'type' => 'product',
        'language' => 'en',
        'title' => 'product 5',
        'node_uid' => 1,
        'revision_uid' => 1,
        'status' => 1,
        'created' => 1279291908,
        'changed' => 1279309993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279309993,
        'body' => [
          [
            'value' => 'body 6',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
        'model' => 'Ship',
        'sell_price' => '3.00',
      ],
    ];
    return $tests;
  }

}
