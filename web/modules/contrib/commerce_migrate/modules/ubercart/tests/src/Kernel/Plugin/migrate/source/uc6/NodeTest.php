<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source\uc6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Ubercart 6 product source plugin.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6\Product
 *
 * @group commerce_migrate
 * @group commerce_migrate_uc6
 */
class NodeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'user',
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
        'type' => 'product',
        'language' => 'en',
        'title' => 'T Shirt',
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
      [
        'nid' => 5,
        'vid' => 5,
        'type' => 'ship',
        'language' => 'en',
        'title' => 'Bag',
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
        'uid' => 2,
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
        'uid' => 2,
        'title' => 'T Shirt',
        'body' => 'body for node 2',
        'teaser' => 'teaser for node 2',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308993,
      ],
      [
        'nid' => 5,
        'vid' => 5,
        'uid' => 2,
        'title' => 'Bag',
        'body' => 'body for node 5',
        'teaser' => 'teaser for node 5',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308993,
      ],
    ];
    $tests[0]['source_data']['uc_products'] = [
      [
        'vid' => '2',
        'nid' => '2',
        'model' => 'item1',
        'list_price' => '30.0000',
        'cost' => '25.0000',
        'sell_price' => '40.000',
        'weight' => '2',
        'weight_units' => 'g',
        'length' => '12',
        'width' => '13',
        'height' => '14',
        'length_units' => 'cm',
        'pkg_qty' => '1',
        'default_qty' => '1',
        'ordering' => '0',
        'shippable' => '1',
      ],
      [
        'vid' => '5',
        'nid' => '5',
        'model' => 'item2',
        'list_price' => '40.0000',
        'cost' => '45.0000',
        'sell_price' => '50.000',
        'weight' => '5',
        'weight_units' => 'g',
        'length' => '22',
        'width' => '23',
        'height' => '24',
        'length_units' => 'cm',
        'pkg_qty' => '1',
        'default_qty' => '1',
        'ordering' => '0',
        'shippable' => '1',
      ],
    ];

    $tests[0]['expected_data'] = [
      [
        'nid' => 2,
        'vid' => 2,
        'type' => 'product',
        'title' => 'T Shirt',
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'body' => 'body for node 2',
        'teaser' => 'teaser for node 2',
        'model' => 'item1',
        'sell_price' => '40.000',
      ],
      [
        'nid' => 5,
        'vid' => 5,
        'type' => 'ship',
        'title' => 'Bag',
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'body' => 'body for node 5',
        'teaser' => 'teaser for node 5',
        'model' => 'item2',
        'sell_price' => '50.000',
      ],
    ];

    $tests[1]['source_data'] = $tests[0]['source_data'];
    $tests[1]['expected_data'] = [
      [
        'nid' => 5,
        'vid' => 5,
        'type' => 'ship',
        'title' => 'Bag',
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'body' => 'body for node 5',
        'teaser' => 'teaser for node 5',
        'model' => 'item2',
        'sell_price' => '50.000',
      ],
    ];
    $tests[1]['expected_count'] = NULL;
    $tests[1]['configuration']['node_type'] = 'ship';

    return $tests;
  }

}
