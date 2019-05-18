<?php

namespace Drupal\commerce_currency_switcher\Event;

use Drupal\commerce_price\Price;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the currency conversion event.
 *
 * @see \Drupal\commerce_currency_switcher\Event\CurrencyEvents
 */
class CurrencyConvertEvent extends Event {

  /**
   * The converted price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $price;

  /**
   * Constructs a new CurrencyConvertEvent.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The converted price.
   */
  public function __construct(Price $price) {
    $this->price = $price;
  }

  /**
   * Gets the converted price.
   *
   * @return \Drupal\commerce_price\Price
   *   The converted price.
   */
  public function getPrice() {
    return $this->price;
  }

  /**
   * Sets the converted price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price object returned by subscribers.
   */
  public function setPrice(Price $price) {
    $this->price = $price;
  }

}
