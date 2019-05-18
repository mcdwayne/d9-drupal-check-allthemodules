<?php

namespace Drupal\Tests\commerce_migrate_ubercart\Kernel\Plugin\migrate\source;

/**
 * Tests the Ubercart order product source plugin without currency data.
 *
 * @covers \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\OrderProduct
 * @group commerce_migrate_uc
 */
class OrderProductNoCurrencyTest extends OrderProductCurrencyTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate_drupal', 'commerce_migrate_ubercart'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = parent::providerSource();

    foreach ($tests as &$test) {
      // Remove currency from the source.
      foreach ($test['source_data']['uc_orders'] as &$data) {
        unset($data['currency']);
      }
      // Set the default currency on each order item and each adjustment.
      foreach ($test['expected_data'] as &$data) {
        $data['currency'] = 'USD';
        foreach ($data['adjustments'] as &$adjustment) {
          $adjustment['currency_code'] = 'USD';
        }
      }
    }
    return $tests;
  }

}
