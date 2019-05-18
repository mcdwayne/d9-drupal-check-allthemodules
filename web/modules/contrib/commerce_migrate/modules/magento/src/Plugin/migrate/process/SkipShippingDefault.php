<?php

namespace Drupal\commerce_migrate_magento\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
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
 *   plugin: magento2_commerce_price
 *   source:
 *     - price
 *     - code
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "magento2_skip_shipping_default"
 * )
 */
class SkipShippingDefault extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      list($address_default_billing, $address_default_shipping_) = $value;
      if ($address_default_shipping_ && !$address_default_billing) {
        throw new MigrateSkipRowException('Skip default shipping row.');
      }
    }
    else {
      throw new MigrateException(sprintf('%s is not an array', var_export($value, TRUE)));
    }
  }

}
