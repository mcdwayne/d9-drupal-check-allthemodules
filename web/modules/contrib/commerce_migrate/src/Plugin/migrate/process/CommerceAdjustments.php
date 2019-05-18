<?php

namespace Drupal\commerce_migrate\Plugin\migrate\process;

use CommerceGuys\Intl\Calculator;
use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts a nested array into Commerce Adjustments.
 *
 * The commerce adjustments process plugin converts adjustment data to
 * Commerce Adjustments. The input value is an indexed array of adjustment data
 * arrays.
 *
 * The properties of the adjustment data array:
 * - type - The adjustment type.
 * - title (optional) - The label for the adjustment. If not supplied, then
 *   label must be supplied.
 * - label (optional) - The label for the adjustment. If not supplied, then
 *   title must be must be supplied.
 * - amount - Numeric value of the adjustment.
 * - currency_code: 3 character currency code string.
 *
 * Example:
 *
 * @code
 * process:
 *  field_adjustment:
 *    plugin: commerce_adjustments
 *    source: input_array
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "commerce_adjustments",
 *   handle_multiples = true
 * )
 */
class CommerceAdjustments extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value) && !empty($value)) {
      $adjustments = [];

      $i = 0;
      foreach ($value as $data) {
        if ($data) {
          $adjustment = [];

          // Thrown an exception if amount and currency_code are not set. Let
          // Price validate the amount.
          $not_set = [];
          foreach (['amount', 'currency_code'] as $property) {
            if (!isset($data[$property])) {
              $not_set[] = $property;
            }
          }
          if ($not_set) {
            if (count($not_set) > 1) {
              throw new MigrateSkipRowException(sprintf("Properties 'amount' and 'currency_code' are not set for adjustment '%s'", var_export($data, TRUE)));
            }
            else {
              throw new MigrateSkipRowException(sprintf("Property '%s' is not set for adjustment '%s'", reset($not_set), var_export($data, TRUE)));
            }
          }

          // Skip the row if the price can't be created.
          try {
            $adjustment['amount'] = new Price(Calculator::trim($data['amount']), $data['currency_code']);
          }
          catch (\InvalidArgumentException $e) {
            throw new MigrateSkipRowException(sprintf('Failed creating price for adjustment %s', var_export($data, TRUE)));
          }

          $adjustment['delta'] = $i++;
          $adjustment['type'] = !empty($data['type']) ? $data['type'] : 'custom';

          // Allow the label to be in a title or label property.
          $adjustment['label'] = !empty($data['label']) ? $data['label'] : '';
          if (empty($adjustment['label'])) {
            $adjustment['label'] = !empty($data['title']) ? $data['title'] : 'Custom';
          }

          $adjustment['percentage'] = !empty($data['percentage']) ? $data['percentage'] : NULL;
          $adjustment['source_id'] = !empty($data['source_id']) ? $data['source_id'] : 'custom';
          $adjustment['included'] = !empty($data['included']) ? $data['included'] : FALSE;
          $adjustment['locked'] = !empty($data['locked']) ? $data['locked'] : TRUE;

          $adjustments[] = new Adjustment($adjustment);
        }
      }
      return $adjustments;
    }

    return $value;
  }

}
