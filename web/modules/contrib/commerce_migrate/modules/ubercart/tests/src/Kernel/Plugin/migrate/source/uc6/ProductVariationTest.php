<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Ubercart product variation source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6\ProductVariation
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class ProductVariationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'migrate_drupal',
    'commerce_migrate_ubercart',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
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
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 0,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'type' => 'ship',
        'language' => 'en',
        'title' => 'ship 1',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290900,
        'changed' => 1279308000,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 0,
      ],
      [
        'nid' => 3,
        'vid' => 3,
        'type' => 'product',
        'language' => 'en',
        'title' => 'product 1',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 0,
      ],
    ];
    $tests[0]['source_data']['node_revisions'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'uid' => 1,
        'title' => 'node title 1',
        'body' => 'body for node 1',
        'teaser' => 'teaser for node 1',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279051598,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'uid' => 1,
        'title' => 'ship 1',
        'body' => 'body for node 2',
        'teaser' => 'teaser for node 2',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308993,
      ],
      [
        'nid' => 3,
        'vid' => 3,
        'uid' => 1,
        'title' => 'product 1',
        'body' => 'body for node 3',
        'teaser' => 'teaser for node 3',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308993,
      ],
    ];

    $tests[0]['source_data']['uc_products'] = [
      [
        'vid' => '2',
        'nid' => '2',
        'model' => 'Heart of Gold',
        'list_price' => '25.0000',
        'cost' => '10.0000',
        'sell_price' => '900.0000',
        'weight' => '10',
        'weight_units' => 'g',
        'length' => '20',
        'width' => '10',
        'height' => '50',
        'length_units' => 'cm',
        'pkg_qty' => '1',
        'default_qty' => '1',
        'unique_hash' => 'a',
        'ordering' => '0',
        'shippable' => '1',
      ],
      [
        'vid' => '3',
        'nid' => '3',
        'model' => 'book',
        'list_price' => '25.0000',
        'cost' => '10.0000',
        'sell_price' => '20.0000',
        'weight' => '10',
        'weight_units' => 'g',
        'length' => '20',
        'width' => '10',
        'height' => '50',
        'length_units' => 'cm',
        'pkg_qty' => '1',
        'default_qty' => '1',
        'unique_hash' => 'a',
        'ordering' => '0',
        'shippable' => '1',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'nid' => 2,
        'vid' => 2,
        'language' => 'en',
        'status' => '1',
        'created' => '1279290900',
        'changed' => '1279308000',
        'comment' => '0',
        'promote' => '1',
        'moderate' => '0',
        'sticky' => '0',
        'tnid' => '2',
        'translate' => '0',
        'title' => 'ship 1',
        'body' => 'body for node 2',
        'teaser' => 'teaser for node 2',
        'log' => '',
        'timestamp' => '1279308993',
        'format' => '1',
        'node_uid' => '1',
        'revision_uid' => '1',
        'model' => 'Heart of Gold',
        'sell_price' => '900.0000',
        'type' => 'ship',
      ],
      [
        'nid' => 3,
        'vid' => 3,
        'language' => 'en',
        'status' => '1',
        'created' => '1279290908',
        'changed' => '1279308993',
        'comment' => '0',
        'promote' => '1',
        'moderate' => '0',
        'sticky' => '0',
        'tnid' => '3',
        'translate' => '0',
        'title' => 'product 1',
        'body' => 'body for node 3',
        'teaser' => 'teaser for node 3',
        'log' => '',
        'timestamp' => '1279308993',
        'format' => '1',
        'node_uid' => '1',
        'revision_uid' => '1',
        'model' => 'book',
        'sell_price' => '20.0000',
        'type' => 'product',
      ],
    ];
    $tests[0]['expected_count'] = 2;

    // The expected results.
    $tests[1]['source_data'] = $tests[0]['source_data'];
    $tests[1]['expected_data'] = [
      [
        'nid' => 2,
        'vid' => 2,
        'language' => 'en',
        'status' => '1',
        'created' => '1279290900',
        'changed' => '1279308000',
        'comment' => '0',
        'promote' => '1',
        'moderate' => '0',
        'sticky' => '0',
        'tnid' => '2',
        'translate' => '0',
        'title' => 'ship 1',
        'body' => 'body for node 2',
        'teaser' => 'teaser for node 2',
        'log' => '',
        'timestamp' => '1279308993',
        'format' => '1',
        'node_uid' => '1',
        'revision_uid' => '1',
        'model' => 'Heart of Gold',
        'sell_price' => '900.0000',
        'type' => 'ship',

      ],
    ];
    $tests[1]['expected_count'] = 1;
    $tests[1]['configuration']['node_type'] = 'ship';

    return $tests;
  }

}
