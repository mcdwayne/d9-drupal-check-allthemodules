<?php

namespace Drupal\commerce_currency_switcher\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the Geoip currency resolve event.
 *
 * @see \Drupal\commerce_currency_switcher\Event\CurrencyEvents
 */
class GeoipCurrencyResolveEvent extends Event {

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * Constructs a new GeoipCurrencyResolveEvent.
   *
   * @param string $currency_code
   *   The currency code.
   */
  public function __construct($currency_code) {
    $this->currencyCode = $currency_code;
  }

  /**
   * Gets the currency code.
   *
   * @return string
   *   The currency code.
   */
  public function getCurrencyCode() {
    return $this->currencyCode;
  }

  /**
   * Sets the currency code.
   *
   * @param string $currencyCode
   *   The currency code.
   */
  public function setCurrencyCode($currencyCode) {
    $this->currencyCode = $currencyCode;
  }

}
