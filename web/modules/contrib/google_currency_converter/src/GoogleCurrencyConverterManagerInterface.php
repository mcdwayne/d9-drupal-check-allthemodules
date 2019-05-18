<?php

namespace Drupal\google_currency_converter;

/**
 * Interface GoogleCurrencyConverterManagerInterface.
 *
 * @package Drupal\google_currency_converter
 */
interface GoogleCurrencyConverterManagerInterface {

  /**
   * Converts the amount from one currency to another.
   *
   * @param int $amount
   *   The Amount to convert.
   * @param string $from
   *   The from currency.
   * @param string $to
   *   The to currency.
   *
   * @return int
   *   Returns the converted amount.
   */
  public function convertAmount($amount, $from, $to);

  /**
   * Returns a list of all available Google Currency Converter Countries.
   *
   * @return array
   *   Returns a list of all available Google Currency Converter Countries.
   */
  public function countries();

}
