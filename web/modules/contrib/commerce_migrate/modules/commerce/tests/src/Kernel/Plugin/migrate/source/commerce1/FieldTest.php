<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 field source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\Field
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class FieldTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'address',
    'commerce',
    'commerce_migrate_commerce',
    'commerce_price',
    'commerce_store',
    'migrate_drupal',
    'options',
    'user',
  ];

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
    ];
    // The source data.
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '3',
        'field_name' => 'commerce_price',
        'type' => 'commerce_price',
        'module' => 'commerce_price',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '1',
        'data' => 'a:7:{s:12:"entity_types";a:1:{i:0;s:16:"commerce_product";}s:7:"indexes";a:1:{s:14:"currency_price";a:2:{i:0;s:6:"amount";i:1;s:13:"currency_code";}}s:8:"settings";a:0:{}s:12:"translatable";i:0;s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";}s:12:"foreign keys";a:0:{}s:2:"id";s:1:"9";}',
        'cardinality' => '-1',
        'translatable' => '0',
        'deleted' => '0',
      ],
      [
        'id' => '10',
        'field_name' => 'field_file',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:13:"display_field";i:0;s:15:"display_default";i:0;s:10:"uri_scheme";s:6:"public";}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}}}}s:12:"foreign keys";a:1:{s:3:"fid";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:3:"fid";s:3:"fid";}}}s:7:"indexes";a:1:{s:3:"fid";a:1:{i:0;s:3:"fid";}}s:2:"id";s:2:"11";}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '1',
        'field_id' => '10',
        'field_name' => 'field_file',
        'entity_type' => 'node',
        'bundle' => 'test_content_type',
        'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"5";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:15:"txt pdf ods odf";s:12:"max_filesize";s:5:"10 MB";s:17:"description_field";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:6:"weight";s:1:"5";s:8:"settings";a:0:{}s:6:"module";s:4:"file";}}s:8:"required";i:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'commerce_price',
        'entity_type' => 'commerce_product',
        'bundle' => 'product',
        'data' => 'a:6:{s:5:"label";s:5:"Price";s:8:"required";b:1;s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:6:"widget";a:4:{s:4:"type";s:19:"commerce_price_full";s:6:"weight";i:0;s:8:"settings";a:1:{s:13:"currency_code";s:7:"default";}s:6:"module";s:14:"commerce_price";}s:7:"display";a:5:{s:4:"full";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:31:"commerce_price_formatted_amount";s:8:"settings";a:1:{s:11:"calculation";s:21:"calculated_sell_price";}s:6:"weight";i:0;s:6:"module";s:14:"commerce_price";}s:9:"line_item";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:31:"commerce_price_formatted_amount";s:8:"settings";a:1:{s:11:"calculation";s:21:"calculated_sell_price";}s:6:"weight";i:0;s:6:"module";s:14:"commerce_price";}s:26:"commerce_line_item_display";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:31:"commerce_price_formatted_amount";s:8:"settings";a:1:{s:11:"calculation";s:21:"calculated_sell_price";}s:6:"weight";i:0;s:6:"module";s:14:"commerce_price";}s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:31:"commerce_price_formatted_amount";s:8:"settings";a:1:{s:11:"calculation";s:21:"calculated_sell_price";}s:6:"weight";i:0;s:6:"module";s:14:"commerce_price";}s:11:"node_teaser";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:31:"commerce_price_formatted_amount";s:8:"settings";a:1:{s:11:"calculation";s:21:"calculated_sell_price";}s:6:"weight";i:0;s:6:"module";s:14:"commerce_price";}}s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
      [
        'id' => '3',
        'field_id' => '10',
        'field_name' => 'field_file',
        'entity_type' => 'node',
        'bundle' => 'product',
        'data' => 'a:6:{s:5:"label";s:4:"File";s:6:"widget";a:5:{s:6:"weight";s:1:"5";s:4:"type";s:12:"file_generic";s:6:"module";s:4:"file";s:6:"active";i:1;s:8:"settings";a:1:{s:18:"progress_indicator";s:8:"throbber";}}s:8:"settings";a:5:{s:14:"file_directory";s:0:"";s:15:"file_extensions";s:15:"txt pdf ods odf";s:12:"max_filesize";s:5:"10 MB";s:17:"description_field";i:1;s:18:"user_register_form";b:0;}s:7:"display";a:1:{s:7:"default";a:5:{s:5:"label";s:5:"above";s:4:"type";s:12:"file_default";s:6:"weight";s:1:"5";s:8:"settings";a:0:{}s:6:"module";s:4:"file";}}s:8:"required";i:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
    ];

    $tests[0]['source_data']['field_revision_commerce_order_total'] = [
      [
        'entity_type' => 'commerce_order',
        'bundle' => 'commerce_order',
        'deleted' => 0,
        'entity_id' => 2,
        'revision_id' => '16',
        'language' => 'und',
        'delta' => 0,
        'commerce_order_total_amount' => '77.23',
        'commerce_order_total_currency_code' => 'USD',
        'data' => 'a:1:{s:10:"components";a:1:{i:0;a:3:{s:4:"name";s:10:"base_price";s:5:"price";a:3:{s:6:"amount";i:3999;s:13:"currency_code";s:3:"USD";s:4:"data";a:0:{}}s:8:"included";b:1;}}}',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'id' => '11',
        'field_name' => 'field_file',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:13:"display_field";i:0;s:15:"display_default";i:0;s:10:"uri_scheme";s:6:"public";}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}}}}s:12:"foreign keys";a:1:{s:3:"fid";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:3:"fid";s:3:"fid";}}}s:7:"indexes";a:1:{s:3:"fid";a:1:{i:0;s:3:"fid";}}s:2:"id";s:2:"11";}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
        'entity_type' => 'node',
        'commerce1_entity_type' => 'node',
      ],
      [
        'id' => '11',
        'field_name' => 'field_file',
        'type' => 'file',
        'module' => 'file',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'data' => 'a:7:{s:12:"translatable";s:1:"0";s:12:"entity_types";a:0:{}s:8:"settings";a:3:{s:13:"display_field";i:0;s:15:"display_default";i:0;s:10:"uri_scheme";s:6:"public";}s:7:"storage";a:5:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";s:1:"1";s:7:"details";a:1:{s:3:"sql";a:2:{s:18:"FIELD_LOAD_CURRENT";a:1:{s:21:"field_data_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}s:19:"FIELD_LOAD_REVISION";a:1:{s:25:"field_revision_field_file";a:3:{s:3:"fid";s:14:"field_file_fid";s:7:"display";s:18:"field_file_display";s:11:"description";s:22:"field_file_description";}}}}}s:12:"foreign keys";a:1:{s:3:"fid";a:2:{s:5:"table";s:12:"file_managed";s:7:"columns";a:1:{s:3:"fid";s:3:"fid";}}}s:7:"indexes";a:1:{s:3:"fid";a:1:{i:0;s:3:"fid";}}s:2:"id";s:2:"11";}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
        'entity_type' => 'product_display',
        'commerce1_entity_type' => 'product_display',
      ],
    ];

    // Set expected count to 1 because custom Field::InitializeIterator()
    // is not executed when counting the rows.
    $tests[0]['expected_count'] = 1;
    return $tests;
  }

}
