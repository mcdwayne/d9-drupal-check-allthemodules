<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Commerce 1 product variation source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\ProductVariations
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ProductVariationTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'migrate_drupal',
    'commerce_migrate_commerce',
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

    // The source data.
    $tests[0]['source_data']['commerce_product'] = [
      [
        'product_id' => '1',
        'revision_id' => '1',
        'sku' => '1',
        'title' => 'Product 1',
        'type' => 'tops',
        'language' => 'und',
        'uid' => '1',
        'status' => '1',
        'created' => '1493287314',
        'changed' => '1493287350',
        'data' => NULL,
      ],
    ];

    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '2',
        'field_name' => 'body',
        'type' => 'text_with_summary',
        'module' => 'text',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:4:"node";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:6:"format";a:2:{s:5:"table";s:13:"filter_format";s:7:"columns";a:1:{s:6:"format";s:6:"format";}}}s:7:"indexes";a:1:{s:6:"format";a:1:{i:0;s:6:"format";}}}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
      [
        'id' => '9',
        'field_name' => 'commerce_price',
        'type' => 'commerce_price',
        'module' => 'commerce_price',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:0;',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
      [
        'id' => '11',
        'field_name' => 'field_product',
        'type' => 'commerce_product_reference',
        'module' => 'commerce_product_reference',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:6:{s:12:"entity_types";a:0:{}s:7:"indexes";a:1:{s:3:"tid";a:1:{i:0;s:3:"tid";}}s:8:"settings";a:2:{s:14:"allowed_values";a:1:{i:0;a:2:{s:10:"vocabulary";s:9:"shoe_size";s:6:"parent";i:0;}}s:21:"options_list_callback";s:29:"title_taxonomy_allowed_values";}s:12:"translatable";i:0;s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:3:"tid";a:2:{s:5:"table";s:18:"taxonomy_term_data";s:7:"columns";a:1:{s:3:"tid";s:3:"tid";}}}}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],

    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '13',
        'field_id' => '11',
        'field_name' => 'field_product',
        'entity_type' => 'node',
        'bundle' => 'tops',
        'data' => 'a:7:{s:11:"description";s:0:"";s:7:"display";a:5:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:6:"module";s:13:"commerce_cart";s:8:"settings";a:6:{s:17:"attributes_single";b:0;s:7:"combine";i:1;s:16:"default_quantity";i:1;s:14:"line_item_type";s:7:"product";s:13:"show_quantity";i:0;s:30:"show_single_product_attributes";b:0;}s:4:"type";s:30:"commerce_cart_add_to_cart_form";s:6:"weight";i:3;}s:4:"full";a:5:{s:5:"label";s:6:"hidden";s:6:"module";s:13:"commerce_cart";s:8:"settings";a:5:{s:7:"combine";i:1;s:16:"default_quantity";i:1;s:14:"line_item_type";s:7:"product";s:13:"show_quantity";i:1;s:30:"show_single_product_attributes";b:0;}s:4:"type";s:30:"commerce_cart_add_to_cart_form";s:6:"weight";i:5;}s:15:"product_in_cart";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:0;}s:12:"product_list";a:5:{s:5:"label";s:6:"hidden";s:6:"module";s:15:"field_extractor";s:8:"settings";a:3:{s:10:"field_name";s:11:"field_color";s:9:"formatter";s:27:"entityreference_entity_view";s:8:"settings";a:2:{s:5:"links";i:1;s:9:"view_mode";s:16:"add_to_cart_form";}}s:4:"type";s:15:"field_extractor";s:6:"weight";i:11;}s:6:"teaser";a:4:{s:5:"label";s:5:"above";s:8:"settings";a:0:{}s:4:"type";s:6:"hidden";s:6:"weight";i:0;}}s:14:"fences_wrapper";s:3:"div";s:5:"label";s:18:"Product variations";s:8:"required";i:1;s:8:"settings";a:3:{s:15:"field_injection";i:1;s:19:"referenceable_types";a:6:{s:10:"bags_cases";i:0;s:6:"drinks";i:0;s:4:"hats";i:0;s:5:"shoes";i:0;s:15:"storage_devices";i:0;s:4:"tops";s:4:"tops";}s:18:"user_register_form";b:0;}s:6:"widget";a:5:{s:6:"active";i:1;s:6:"module";s:18:"inline_entity_form";s:8:"settings";a:2:{s:6:"fields";a:0:{}s:13:"type_settings";a:5:{s:14:"allow_existing";i:0;s:18:"autogenerate_title";i:1;s:17:"delete_references";i:1;s:14:"match_operator";s:8:"CONTAINS";s:22:"use_variation_language";i:1;}}s:4:"type";s:18:"inline_entity_form";s:6:"weight";i:2;}}',
        'deleted' => '0',
      ],
      [
        'id' => '9',
        'field_id' => '9',
        'field_name' => 'commerce_price',
        'entity_type' => 'commerce_product',
        'bundle' => 'tops',
        'data' => 'a:0;',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_data_commerce_price'] = [
      [
        'entity_type' => 'commerce_product',
        'bundle' => 'tops',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'und',
        'delta' => '0',
        'commerce_price_amount' => '123',
        'commerce_price_currency_code' => 'USD',
        'commerce_price_data' => NULL,
      ],
    ];
    $tests[0]['source_data']['field_revision_commerce_price'] = [
      [
        'entity_type' => 'commerce_product',
        'bundle' => 'tops',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'und',
        'delta' => '0',
        'commerce_price_amount' => '123',
        'commerce_price_currency_code' => 'USD',
        'commerce_price_data' => NULL,
      ],
    ];
    $tests[0]['source_data']['node_type'] = [
      [
        'type' => 'page',
        'name' => 'Page',
        'base' => 'node',
        'description' => 'Description',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'page',
      ],
      [
        'type' => 'tops',
        'name' => 'Tops',
        'base' => 'node',
        'description' => 'Shirts',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'tops',
      ],
    ];

    // The expected data.
    $tests[0]['expected_data'] = [
      [
        'product_id' => '1',
        'revision_id' => '1',
        'sku' => '1',
        'title' => 'Product 1',
        'type' => 'tops',
        'language' => 'und',
        'uid' => '1',
        'status' => '1',
        'created' => '1493287314',
        'changed' => '1493287350',
        'data' => NULL,
        'commerce_price' => [
          [
            'amount' => '123',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
            'data' => NULL,
          ],
        ],
      ],
    ];

    return $tests;
  }

}
