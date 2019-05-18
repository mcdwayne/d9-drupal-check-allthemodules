<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

/**
 * Tests the Commerce 1 order item type source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\Order
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class OrderTest extends SourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'address',
    'commerce',
    'commerce_migrate_commerce',
    'commerce_price',
    'commerce_store',
    'migrate_drupal',
    'options',
    'path',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
    $tests[0]['source_data']['commerce_order'] = [
      [
        'order_id' => '2',
        'order_number' => '2',
        'revision_id' => '16',
        'type' => 'commerce_order',
        'uid' => '4',
        'mail' => 'customer@example.com',
        'status' => 'pending',
        'created' => '12345678',
        'changed' => '12345679',
        'hostname' => '192,168,1,1',
        'data' => 'a:1:{s:8:"profiles";a:2:{s:24:"customer_profile_billing";s:1:"1";s:25:"customer_profile_shipping";s:1:"2";}}',
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '3',
        'field_name' => 'commerce_order_total',
        'type' => 'commerce_price',
        'module' => 'commerce_price',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '1',
        'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:14:"commerce_order";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:14:"currency_price";a:2:{i:0;s:6:"amount";i:1;s:13:"currency_code";}}}',
        'cardinality' => '-1',
        'translatable' => '0',
        'deleted' => '0',
      ],
      [
        'id' => '11',
        'field_name' => 'commerce_line_items',
        'type' => 'commerce_line_item_reference',
        'module' => 'commerce_line_item',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '0',
        'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:14:"commerce_order";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:1:{s:12:"line_item_id";a:2:{s:5:"table";s:18:"commerce_line_item";s:7:"columns";a:1:{s:12:"line_item_id";s:12:"line_item_id";}}}s:7:"indexes";a:1:{s:12:"line_item_id";a:1:{i:0;s:12:"line_item_id";}}}',
        'cardinality' => '-1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '4',
        'field_id' => '6',
        'field_name' => 'commerce_total',
        'entity_type' => 'commerce_line_item',
        'bundle' => 'shipping',
        'data' => 'a:0:{};',
        'deleted' => '0',
      ],
      [
        'id' => '11',
        'field_id' => '2',
        'field_name' => 'commerce_line_items',
        'entity_type' => 'commerce_order',
        'bundle' => 'commerce_order',
        'data' => 'a:6:{s:5:"label";s:10:"Line items";s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:6:"widget";a:4:{s:4:"type";s:26:"commerce_line_item_manager";s:6:"weight";i:-10;s:8:"settings";a:0:{}s:6:"module";s:18:"commerce_line_item";}s:7:"display";a:3:{s:7:"default";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:33:"commerce_line_item_reference_view";s:6:"weight";i:-10;s:8:"settings";a:1:{s:4:"view";s:32:"commerce_line_item_table|default";}s:6:"module";s:18:"commerce_line_item";}s:8:"customer";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:33:"commerce_line_item_reference_view";s:6:"weight";i:-10;s:8:"settings";a:1:{s:4:"view";s:32:"commerce_line_item_table|default";}s:6:"module";s:18:"commerce_line_item";}s:13:"administrator";a:5:{s:5:"label";s:6:"hidden";s:4:"type";s:33:"commerce_line_item_reference_view";s:6:"weight";i:-10;s:8:"settings";a:1:{s:4:"view";s:32:"commerce_line_item_table|default";}s:6:"module";s:18:"commerce_line_item";}}s:8:"required";b:0;s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
      [
        'id' => '21',
        'field_id' => '3',
        'field_name' => 'commerce_order_total',
        'entity_type' => 'commerce_order',
        'bundle' => 'commerce_order',
        'data' => 'a:6:{s:5:"label";s:11:"Order total";s:8:"required";b:1;s:8:"settings";a:1:{s:18:"user_register_form";b:0;}s:6:"widget";a:4:{s:4:"type";s:19:"commerce_price_full";s:6:"weight";i:-8;s:8:"settings";a:1:{s:13:"currency_code";s:7:"default";}s:6:"module";s:14:"commerce_price";}s:7:"display";a:4:{s:13:"administrator";a:5:{s:4:"type";s:35:"commerce_price_formatted_components";s:5:"label";s:6:"hidden";s:8:"settings";a:1:{s:11:"calculation";b:0;}s:6:"weight";i:-8;s:6:"module";s:14:"commerce_price";}s:8:"customer";a:5:{s:4:"type";s:35:"commerce_price_formatted_components";s:5:"label";s:6:"hidden";s:8:"settings";a:1:{s:11:"calculation";b:0;}s:6:"weight";i:-8;s:6:"module";s:14:"commerce_price";}s:7:"default";a:5:{s:4:"type";s:35:"commerce_price_formatted_components";s:5:"label";s:6:"hidden";s:8:"settings";a:1:{s:11:"calculation";b:0;}s:6:"weight";i:-8;s:6:"module";s:14:"commerce_price";}s:11:"node_teaser";a:5:{s:4:"type";s:35:"commerce_price_formatted_components";s:5:"label";s:6:"hidden";s:8:"settings";a:1:{s:11:"calculation";b:0;}s:6:"weight";i:-8;s:6:"module";s:14:"commerce_price";}}s:11:"description";s:0:"";}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_revision_commerce_line_items'] = [
      [
        'entity_type' => 'commerce_order',
        'bundle' => 'commerce_order',
        'deleted' => 0,
        'entity_id' => 2,
        'revision_id' => '16',
        'language' => 'und',
        'delta' => 0,
        'commerce_line_items_line_item_id' => '7',
      ],
    ];
    $tests[0]['source_data']['field_revision_commerce_total'] = [
      [
        'entity_type' => 'commerce_line_item',
        'bundle' => 'product',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'und',
        'delta' => 0,
        'commerce_total_amount' => '1234',
        'commerce_total_currency_code' => 'USD',
        'commerce_total_data' => 'a:0:{};',
      ],
      [
        'entity_type' => 'commerce_line_item',
        'bundle' => 'shipping',
        'deleted' => 0,
        'entity_id' => 11,
        'revision_id' => 11,
        'language' => 'und',
        'delta' => 0,
        'commerce_total_amount' => '10',
        'commerce_total_currency_code' => 'USD',
        'commerce_total_data' => 'a:0:{};',
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
    $tests[0]['source_data']['commerce_line_item'] = [
      [
        'line_item_id' => '1',
        'order_id' => '2',
        'type' => 'product',
        'line_item_label' => 'sku 1',
        'quantity' => '2',
        'data' => 'a:0{}',
        'created' => '1492868907',
        'changed' => '1498620003',
      ],
      [
        'line_item_id' => '11',
        'order_id' => '2',
        'type' => 'shipping',
        'line_item_label' => 'Express',
        'quantity' => '1',
        'data' => 'a:1:{s:16:"shipping_service";a:14:{s:4:"name";s:16:"express_shipping";s:4:"base";s:16:"express_shipping";s:13:"display_title";s:32:"Express shipping: 1 business day";s:11:"description";s:48:"An express shipping service with additional fee.";s:15:"shipping_method";s:9:"flat_rate";s:15:"rules_component";b:1;s:15:"price_component";s:26:"flat_rate_express_shipping";s:6:"weight";i:0;s:9:"callbacks";a:4:{s:4:"rate";s:37:"commerce_flat_rate_service_rate_order";s:12:"details_form";s:29:"express_shipping_details_form";s:21:"details_form_validate";s:38:"express_shipping_details_form_validate";s:19:"details_form_submit";s:36:"express_shipping_details_form_submit";}s:6:"module";s:18:"commerce_flat_rate";s:5:"title";s:16:"Express Shipping";s:9:"base_rate";a:3:{s:6:"amount";s:4:"1500";s:13:"currency_code";s:3:"USD";s:4:"data";a:0:{}}s:4:"data";a:0:{}s:10:"admin_list";b:1;}}',
        'created' => '1492868907',
        'changed' => '1498620003',
      ],
      [
        'line_item_id' => '2',
        'order_id' => '2',
        'type' => 'tax',
        'line_item_label' => 'Sales Tax',
        'quantity' => '1',
        'data' => 'a:0{}',
        'created' => '1492868907',
        'changed' => '1498620003',
      ],
    ];
    $tests[0]['source_data']['field_data_commerce_total'] = [
      [
        'entity_type' => 'commerce_line_item',
        'bundle' => 'product',
        'deleted' => 0,
        'entity_id' => 1,
        'delta' => 0,
        'commerce_total_amount' => '1234',
        'commerce_total_currency_code' => 'USD',
      ],
      [
        'entity_type' => 'commerce_line_item',
        'bundle' => 'shipping',
        'deleted' => 0,
        'entity_id' => 11,
        'delta' => 0,
        'commerce_total_amount' => '10',
        'commerce_total_currency_code' => 'USD',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] =
      [
        [
          'order_id' => '2',
          'order_number' => '2',
          'revision_id' => '16',
          'uid' => '4',
          'mail' => 'customer@example.com',
          'status' => 'pending',
          'created' => '12345678',
          'changed' => '12345679',
          'hostname' => '192,168,1,1',
          'data' => unserialize('a:1:{s:8:"profiles";a:2:{s:24:"customer_profile_billing";s:1:"1";s:25:"customer_profile_shipping";s:1:"2";}}'),
          'commerce_line_items' => [
            ['line_item_id' => 7],
          ],
          'commerce_order_total' => [
            [
              'amount' => '77.23',
              'currency_code' => 'USD',
              'fraction_digits' => 2,
            ],
          ],
          'shipping_line_items' => [
            [
              'line_item_id' => '11',
              'order_id' => '2',
              'type' => 'shipping',
              'line_item_label' => 'Express',
              'quantity' => '1',
              'data' => [
                'shipping_service' => [
                  'name' => "express_shipping",
                  "base" => "express_shipping",
                  "display_title" => "Express shipping: 1 business day",
                  "description" => "An express shipping service with additional fee.",
                  "shipping_method" => "flat_rate",
                  'rules_component' => TRUE,
                  'callbacks' => [
                    'rate' => 'commerce_flat_rate_service_rate_order',
                    'details_form' => 'express_shipping_details_form',
                    'details_form_validate' => 'express_shipping_details_form_validate',
                    'details_form_submit' => 'express_shipping_details_form_submit',
                  ],
                  'price_component' => 'flat_rate_express_shipping',
                  'weight' => 0,
                  'module' => 'commerce_flat_rate',
                  'title' => 'Express Shipping',
                  'base_rate' => [
                    'amount' => '1500',
                    'currency_code' => 'USD',
                    'data' => [],
                  ],
                  'data' => [],
                  'admin_list' => TRUE,
                ],
              ],
              'created' => '1492868907',
              'changed' => '1498620003',
              'commerce_total' => [
                [
                  'amount' => '10',
                  'currency_code' => 'USD',
                  'data' => [],
                  'fraction_digits' => 2,
                ],
              ],
            ],
          ],
          'type' => 'commerce_order',
        ],
      ];
    return $tests;
  }

}
