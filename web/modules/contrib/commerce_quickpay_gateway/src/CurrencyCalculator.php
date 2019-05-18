<?php

namespace Drupal\commerce_quickpay_gateway;

class CurrencyCalculator {
  const MULTIPLIER = 100;

  /**
   * Returns the amount adjusted by the multiplier for the currency.
   *
   * @inheritdoc
   */
  public function wireAmount($amount) {
    return (function_exists('bcmul') ? bcmul($amount, self::MULTIPLIER) : $amount * self::MULTIPLIER);
  }

  /**
   * Reverses wireAmount().
   *
   * @inheritdoc
   */
  public function unwireAmount($amount) {
    return (function_exists('bcdiv') ?
      bcdiv($amount, self::MULTIPLIER, log10(self::MULTIPLIER)) :
      $amount / self::MULTIPLIER);
  }
}
