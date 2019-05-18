<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Plugin\migrate\source\commerce1;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the Commerce 1 order item type source plugin.
 *
 * @covers \Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1\OrderItemType
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class OrderItemTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_commerce'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];
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
        'line_item_id' => '3',
        'order_id' => '2',
        'type' => 'shipping',
        'line_item_label' => 'Express',
        'quantity' => '2',
        'data' => 'a:1:{s:16:"shipping_service";a:14:{s:4:"name";s:16:"express_shipping";s:4:"base";s:16:"express_shipping";s:13:"display_title";s:32:"Express shipping: 1 business day";s:11:"description";s:48:"An express shipping service with additional fee.";s:15:"shipping_method";s:9:"flat_rate";s:15:"rules_component";b:1;s:15:"price_component";s:26:"flat_rate_express_shipping";s:6:"weight";i:0;s:9:"callbacks";a:4:{s:4:"rate";s:37:"commerce_flat_rate_service_rate_order";s:12:"details_form";s:29:"express_shipping_details_form";s:21:"details_form_validate";s:38:"express_shipping_details_form_validate";s:19:"details_form_submit";s:36:"express_shipping_details_form_submit";}s:6:"module";s:18:"commerce_flat_rate";s:5:"title";s:16:"Express Shipping";s:9:"base_rate";a:3:{s:6:"amount";s:4:"1500";s:13:"currency_code";s:3:"USD";s:4:"data";a:0:{}}s:4:"data";a:0:{}s:10:"admin_list";b:1;}}',
        'created' => '1492868907',
        'changed' => '1498620003',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      ['type' => 'product'],
      ['type' => 'shipping'],
    ];

    return $tests;
  }

}
