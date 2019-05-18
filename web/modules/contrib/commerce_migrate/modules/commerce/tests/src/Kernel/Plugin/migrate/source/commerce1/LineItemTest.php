<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 line item source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\LineItem
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class LineItemTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_commerce'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // Tests getting all line items.
    $tests[0]['source_data']['commerce_line_item'] = [
      [
        'line_item_id' => '1',
        'order_id' => '2',
        'type' => 'product',
        'line_item_label' => 'sku 1',
        'quantity' => '2',
        'data' => 'a:0:{};',
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
    ];
    $tests[0]['source_data']['commerce_product'] = [
      [
        'product_id' => '2',
        'revision_id' => '3',
        'sku' => 'sku 1',
        'title' => 'Product A title',
        'type' => 'shirts',
        'language' => 'und',
        'uid' => '3',
        'status' => '1',
        'created' => '1493287314',
        'changed' => '1493287314',
        'data' => 'a:0:{};',
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '2',
        'field_name' => 'commerce_unit_price',
        'type' => 'commerce_price',
        'module' => 'commerce_price',
        'active' => '1',
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => '1',
        'locked' => '1',
        'data' => 'a:6:{s:12:"entity_types";a:1:{i:0;s:18:"commerce_line_item";}s:12:"translatable";b:0;s:8:"settings";a:0:{}s:7:"storage";a:4:{s:4:"type";s:17:"field_sql_storage";s:8:"settings";a:0:{}s:6:"module";s:17:"field_sql_storage";s:6:"active";i:1;}s:12:"foreign keys";a:0:{}s:7:"indexes";a:1:{s:14:"currency_price";a:2:{i:0;s:6:"amount";i:1;s:13:"currency_code";}}}',
        'cardinality' => '1',
        'translatable' => '0',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'commerce_unit_price',
        'entity_type' => 'commerce_line_item',
        'bundle' => 'product',
        'data' => 'a:0:{};',
        'deleted' => '0',
      ],
      [
        'id' => '5',
        'field_id' => '2',
        'field_name' => 'commerce_unit_price',
        'entity_type' => 'commerce_line_item',
        'bundle' => 'shipping',
        'data' => 'a:0:{};',
        'deleted' => '0',
      ],
      [
        'id' => '3',
        'field_id' => '6',
        'field_name' => 'commerce_total',
        'entity_type' => 'commerce_line_item',
        'bundle' => 'product',
        'data' => 'a:0:{};',
        'deleted' => '0',
      ],
      [
        'id' => '4',
        'field_id' => '6',
        'field_name' => 'commerce_total',
        'entity_type' => 'commerce_line_item',
        'bundle' => 'shipping',
        'data' => 'a:0:{};',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_data_commerce_unit_price'] = [
      [
        'entity_type' => 'commerce_line_item',
        'bundle' => 'product',
        'deleted' => 0,
        'entity_id' => 1,
        'delta' => 0,
        'commerce_unit_price_amount' => '1234',
        'commerce_unit_price_currency_code' => 'USD',
      ],
      [
        'entity_type' => 'commerce_line_item',
        'bundle' => 'shipping',
        'deleted' => 0,
        'entity_id' => 11,
        'delta' => 0,
        'commerce_unit_price_amount' => '10',
        'commerce_unit_price_currency_code' => 'USD',
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
    $tests[0]['expected_data'] = [
      [
        'line_item_id' => '1',
        'order_id' => '2',
        'type' => 'product',
        'line_item_label' => 'sku 1',
        'quantity' => '2',
        'data' => [],
        'created' => '1492868907',
        'changed' => '1498620003',
        'title' => 'Product A title',
        'commerce_unit_price' => [
          [
            'amount' => '1234',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
        'commerce_total' => [
          [
            'amount' => '1234',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
      ],
      [
        'line_item_id' => '11',
        'order_id' => '2',
        'type' => 'shipping',
        'line_item_label' => 'Express',
        'quantity' => '1',
        'data' => unserialize('a:1:{s:16:"shipping_service";a:14:{s:4:"name";s:16:"express_shipping";s:4:"base";s:16:"express_shipping";s:13:"display_title";s:32:"Express shipping: 1 business day";s:11:"description";s:48:"An express shipping service with additional fee.";s:15:"shipping_method";s:9:"flat_rate";s:15:"rules_component";b:1;s:15:"price_component";s:26:"flat_rate_express_shipping";s:6:"weight";i:0;s:9:"callbacks";a:4:{s:4:"rate";s:37:"commerce_flat_rate_service_rate_order";s:12:"details_form";s:29:"express_shipping_details_form";s:21:"details_form_validate";s:38:"express_shipping_details_form_validate";s:19:"details_form_submit";s:36:"express_shipping_details_form_submit";}s:6:"module";s:18:"commerce_flat_rate";s:5:"title";s:16:"Express Shipping";s:9:"base_rate";a:3:{s:6:"amount";s:4:"1500";s:13:"currency_code";s:3:"USD";s:4:"data";a:0:{}}s:4:"data";a:0:{}s:10:"admin_list";b:1;}}'),
        'created' => '1492868907',
        'changed' => '1498620003',
        'title' => 'Express',
        'commerce_unit_price' => [
          [
            'amount' => '10',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
        'commerce_total' => [
          [
            'amount' => '10',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
      ],
    ];
    $tests[0]['expected_count'] = NULL;
    // Empty configuration will return all types.
    $tests[0]['configuration'] = [];

    // Tests getting only product line items.
    $tests[1] = $tests[0];
    $tests[1]['expected_data'] = [
      [
        'line_item_id' => '1',
        'order_id' => '2',
        'type' => 'product',
        'line_item_label' => 'sku 1',
        'quantity' => '2',
        'data' => [],
        'created' => '1492868907',
        'changed' => '1498620003',
        'title' => 'Product A title',
        'commerce_unit_price' => [
          [
            'amount' => '1234',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
        'commerce_total' => [
          [
            'amount' => '1234',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
      ],
    ];
    // Gets only product line items.
    $tests[1]['configuration']['line_item_type'] = 'product';

    // Tests getting only shipping line items.
    $tests[2] = $tests[0];
    $tests[2]['expected_data'] = [
      [
        'line_item_id' => '11',
        'order_id' => '2',
        'type' => 'shipping',
        'line_item_label' => 'Express',
        'quantity' => '1',
        'data' => unserialize('a:1:{s:16:"shipping_service";a:14:{s:4:"name";s:16:"express_shipping";s:4:"base";s:16:"express_shipping";s:13:"display_title";s:32:"Express shipping: 1 business day";s:11:"description";s:48:"An express shipping service with additional fee.";s:15:"shipping_method";s:9:"flat_rate";s:15:"rules_component";b:1;s:15:"price_component";s:26:"flat_rate_express_shipping";s:6:"weight";i:0;s:9:"callbacks";a:4:{s:4:"rate";s:37:"commerce_flat_rate_service_rate_order";s:12:"details_form";s:29:"express_shipping_details_form";s:21:"details_form_validate";s:38:"express_shipping_details_form_validate";s:19:"details_form_submit";s:36:"express_shipping_details_form_submit";}s:6:"module";s:18:"commerce_flat_rate";s:5:"title";s:16:"Express Shipping";s:9:"base_rate";a:3:{s:6:"amount";s:4:"1500";s:13:"currency_code";s:3:"USD";s:4:"data";a:0:{}}s:4:"data";a:0:{}s:10:"admin_list";b:1;}}'),
        'created' => '1492868907',
        'changed' => '1498620003',
        'title' => 'Express',
        'commerce_unit_price' => [
          [
            'amount' => '10',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
        'commerce_total' => [
          [
            'amount' => '10',
            'currency_code' => 'USD',
            'fraction_digits' => 2,
          ],
        ],
      ],
    ];
    // Gets only shipping line items.
    $tests[2]['configuration']['line_item_type'] = 'shipping';

    return $tests;
  }

}
