<?php
/**
 * Contains CurrencyConverterInterface.php.
 */

namespace Drupal\simple_currency_converter\CurrencyConverter;

interface CurrencyConverterInterface {

  /**
   * Converts an amount from one currency to another.
   *
   * @param $from_currency
   * @param $to_currency
   * @param $amount
   *
   * @return mixed
   */
  public function convert($from_currency, $to_currency, $amount);

  /**
   * Returns an array of supported currencies.
   *
   * @return array
   */
  public function currencies();

}
