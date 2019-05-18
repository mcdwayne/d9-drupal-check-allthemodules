<?php

namespace Drupal\commerce_migrate_shopify\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Creates a price array from the input value.
 *
 * Build a keyed array where price is the first value in the input array and the
 * currency code is the second. If there is no price value, an empty array is
 * returned.
 *
 * Example:
 * @code
 * price:
 *   plugin: shopify_commerce_price
 *   source:
 *     - price
 *     - code
 * @endcode
 *
 * When price = 12.00 and code is 'CAD', a an associative array, where
 * 'number' => 12.00 and 'currency_code => 'CAD' is returned.
 *
 * @MigrateProcessPlugin(
 *   id = "shopify_commerce_price"
 * )
 */
class CommercePrice extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_value = NULL;
    $number = $value[0];
    if ($number) {
      $new_value = [
        'number' => $number,
        'currency_code' => strtoupper($value[1]),
      ];
    }
    return $new_value;
  }

}
