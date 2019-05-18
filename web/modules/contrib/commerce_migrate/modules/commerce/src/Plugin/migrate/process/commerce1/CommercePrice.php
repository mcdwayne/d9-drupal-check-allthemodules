<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\process\commerce1;

use Drupal\commerce_price\Calculator;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Scales the price from Commerce 1 to Commerce 2.
 *
 * This plugin is put in the pipeline by field migration for fields of type
 * 'commerce_price'. It is also used in the product variation migration and
 * the order item migration.
 *
 * The commerce_price process plugin is put in the pipeline by field migrations
 * for fields of type 'commerce_price'. It is also used in the product variation
 * migration and the order item migration.
 *
 * This plugin is used to convert the Commerce 1 price array to a Commerce 2
 * price array. The source value is an  associative  array with keys, 'amount',
 * 'currency_code' and 'fraction_digits'.
 *
 * Input array::
 * - amount: The price number
 * - currency_code: The currency code.
 * - fraction_digits: The number of fraction digits for this given currency.
 *
 * Returned array::
 * - number: The converted price number
 * - currency_code: The currency code.
 *
 * An empty array is returned for all errors.
 *
 * @code
 * plugin: commerce1_migrate_commerce_price
 * source: commerce1_price_array
 * @endcode
 *
 * When input the input is
 *  [
 *    'amount' => '123',
 *    'currency_code' => 'NZD',
 *    'fraction_digits' => 3,
 * ];
 * The output is
 *  [
 *    'number' => '0.123',
 *    'currency_code' => 'NZD',
 * ];
 *
 * @MigrateProcessPlugin(
 *   id = "commerce1_migrate_commerce_price"
 * )
 */
class CommercePrice extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateSkipRowException(sprintf("CommercePrice input is not an array for destination '%s'", $destination_property));
    }

    // If the destination is a unit price then use the base price component, if
    // if it is available.
    if (strstr('unit_price', $destination_property)) {
      if (isset($value['data']['components'])) {
        foreach ($value['data']['components'] as $component) {
          if ($component['name'] === 'base_price') {
            $value['amount'] = $component['price']['amount'];
            $value['currency_code'] = $component['price']['currency_code'];
            break;
          }
        }
      }
    }
    if (isset($value['amount']) && isset($value['currency_code']) && isset($value['fraction_digits']) && $value['fraction_digits'] >= 0) {
      $new_value = [
        'number' => Calculator::divide($value['amount'], bcpow(10, $value['fraction_digits'])),
        'currency_code' => $value['currency_code'],
      ];
    }
    else {
      throw new MigrateSkipRowException(sprintf("CommercePrice input array is invalid for destination '%s'", $destination_property));
    }

    return $new_value;
  }

}
