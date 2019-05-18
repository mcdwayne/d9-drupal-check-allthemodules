<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\Tests\commerce_migrate\Kernel\CommerceMigrateTestTrait;
use Drupal\commerce_shipping\Entity\ShippingMethod;

/**
 * Tests flat rate shipping migration from Commerce 1.
 *
 * @requires module commerce_shipping
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class ShippingFlatRateTest extends Commerce1TestBase {

  use CommerceMigrateTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_shipping',
    'commerce_store',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('commerce_shipping_method');
    $this->executeMigration('commerce1_shipping_flat_rate');
  }

  /**
   * Test flat rate shipping method migration.
   */
  public function testShippingFlatRate() {
    // The Commerce 1 source does not have an id value so the methods may be in
    // any order but the common assert method needs a shipping method id. So, we
    // loop through each method and then set the test values accordingly. A
    // counter is used to check that all 3 methods are tested.
    $methods_tested_count = 0;
    for ($i = 1; $i < 4; $i++) {
      $shipping_method = ShippingMethod::load($i);
      $name = $shipping_method->getName();
      switch ($name) {
        case 'Express Shipping':
          $type = [
            'label' => 'Express Shipping',
            'rate_amount' =>
              [
                'number' => '15.00',
                'currency_code' => 'USD',
              ],
          ];
          $methods_tested_count++;
          break;

        case 'Free Shipping':
          $type = [
            'label' => 'Free Shipping',
            'rate_amount' =>
              [
                'number' => '0.00',
                'currency_code' => 'USD',
              ],
          ];
          $methods_tested_count++;
          break;

        case 'Standard Shipping':
          $type = [
            'label' => 'Standard Shipping',
            'rate_amount' =>
              [
                'number' => '8.00',
                'currency_code' => 'USD',
              ],
          ];
          $methods_tested_count++;
          break;
      }
      $type['id'] = $i;
      $type['stores'] = ['1'];
      $this->assertShippingMethod($type);
    }

    // Check that all three methods were tested.
    $this->assertSame(3, $methods_tested_count);
  }

}
