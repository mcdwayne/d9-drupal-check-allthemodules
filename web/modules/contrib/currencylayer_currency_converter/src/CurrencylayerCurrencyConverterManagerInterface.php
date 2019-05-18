<?php

namespace Drupal\currencylayer_currency_converter;

/**
 * Interface CurrencylayerCurrencyConverterManagerInterface.
 *
 * @package Drupal\currencylayer_currency_converter
 */
interface CurrencylayerCurrencyConverterManagerInterface {

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
   * Returns a list of all available Currencylayer currency converter Countries.
   *
   * @return array
   *   Returns a list of all available Currencylayer currency converter Countries.
   */
  public function countries();

}
