<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Builds an array of adjustment data.
 *
 * @MigrateProcessPlugin(
 *   id = "commerce1_order_adjustment_shipping"
 * )
 */
class OrderAdjustmentShipping extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $adjustment = [];

    // It is not an error if $value is not an array. In that case return an
    // empty array.
    if (is_array($value)) {

      if (!isset($value['commerce_total'])) {
        throw new MigrateSkipRowException(sprintf("Adjustment does not have a total for destination '%s'", $destination_property));
      }

      $total = $value['commerce_total'][0];
      if (!isset($total['amount'])) {
        throw new MigrateSkipRowException("Adjustment total amount does not exist for destination '%s'", $destination_property);
      }

      if (!isset($total['currency_code'])) {
        throw new MigrateSkipRowException("Adjustment currency code does not exist for destination '%s'", $destination_property);
      }

      $fraction_digits = isset($total['fraction_digits']) ? $total['fraction_digits'] : '2';

      // Scale the incoming price by the fraction digits.
      $input = [
        'amount' => $total['amount'],
        'fraction_digits' => $fraction_digits,
        'currency_code' => $total['currency_code'],
      ];
      $price = new CommercePrice([], 'price', '');
      $price_scaled = $price->transform($input, $migrate_executable, $row, NULL);

      // Build the adjustment array.
      $adjustment = [
        'type' => 'shipping',
        'label' => isset($value['line_item_label']) ? $value['line_item_label'] : 'Shipping',
        'amount' => $price_scaled['number'],
        'currency_code' => $price_scaled['currency_code'],
        'sourceId' => 'custom',
        'included' => FALSE,
        'locked' => TRUE,
      ];
    }
    return $adjustment;
  }

}
