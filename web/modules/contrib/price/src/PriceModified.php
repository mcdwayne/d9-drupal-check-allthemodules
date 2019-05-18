<?php

namespace Drupal\price;

use Drupal\price\Exception\CurrencyMismatchException;

/**
 * Provides a value object for monetary values.
 */
final class PriceModified {

  /**
   * The number.
   *
   * @var string
   */
  protected $number;

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * The modifier.
   *
   * @var string
   */
  protected $modifier;

  /**
   * Constructs a new PriceModified object.
   *
   * @param string $number
   *   The number.
   * @param string $currency_code
   *   The currency code.
   * @param string $modifier
   *   The modifier.
   */
  public function __construct($number, $currency_code, $modifier) {
    Calculator::assertNumberFormat($number);
    $this->assertCurrencyCodeFormat($currency_code);

    $this->number = (string) $number;
    $this->currencyCode = strtoupper($currency_code);
    $this->modifier = $modifier;
  }

  /**
   * Gets the number.
   *
   * @return string
   *   The number.
   */
  public function getNumber() {
    return $this->number;
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
   * Gets the modifier.
   *
   * @return string
   *   The modifier.
   */
  public function getModifier() {
    return $this->modifier;
  }

  /**
   * Gets the string representation of the price.
   *
   * @return string
   *   The string representation of the price.
   */
  public function __toString() {
    return $this->modifier . ' ' . Calculator::trim($this->number) . ' ' . $this->currencyCode;
  }

  /**
   * Gets the array representation of the price.
   *
   * @return array
   *   The array representation of the price.
   */
  public function toArray() {
    return ['number' => $this->number, 'currency_code' => $this->currencyCode, 'modifier' => $this->modifier];
  }

  /**
   * Converts the current price to the given currency.
   *
   * @param string $currency_code
   *   The currency code.
   * @param string $rate
   *   A currency rate corresponding to the currency code.
   *
   * @return static
   *   The resulting price.
   */
  public function convert($currency_code, $rate = '1') {
    $new_number = Calculator::multiply($this->number, $rate);
    return new static($new_number, $currency_code, $this->modifier);
  }

  /**
   * Adds the given price to the current price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return static
   *   The resulting price.
   */
  public function add(PriceModified $price) {
    $this->assertSameCurrency($this, $price);
    $new_number = Calculator::add($this->number, $price->getNumber());
    return new static($new_number, $this->currencyCode, $this->modifier);
  }

  /**
   * Subtracts the given price from the current price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return static
   *   The resulting price.
   */
  public function subtract(PriceModified $price) {
    $this->assertSameCurrency($this, $price);
    $new_number = Calculator::subtract($this->number, $price->getNumber());
    return new static($new_number, $this->currencyCode, $this->modifier);
  }

  /**
   * Multiplies the current price by the given number.
   *
   * @param string $number
   *   The number.
   *
   * @return static
   *   The resulting price.
   */
  public function multiply($number) {
    $new_number = Calculator::multiply($this->number, $number);
    return new static($new_number, $this->currencyCode, $this->modifier);
  }

  /**
   * Divides the current price by the given number.
   *
   * @param string $number
   *   The number.
   *
   * @return static
   *   The resulting price.
   */
  public function divide($number) {
    $new_number = Calculator::divide($this->number, $number);
    return new static($new_number, $this->currencyCode, $this->modifier);
  }

  /**
   * Compares the current price with the given price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return int
   *   0 if both prices are equal, 1 if the first one is greater, -1 otherwise.
   */
  public function compareTo(PriceModified $price) {
    $this->assertSameCurrency($this, $price);
    return Calculator::compare($this->number, $price->getNumber());
  }

  /**
   * Gets whether the current price is zero.
   *
   * @return bool
   *   TRUE if the price is zero, FALSE otherwise.
   */
  public function isZero() {
    return Calculator::compare($this->number, '0') == 0;
  }

  /**
   * Gets whether the current price is equivalent to the given price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return bool
   *   TRUE if the prices are equal, FALSE otherwise.
   */
  public function equals(PriceModified $price) {
    return $this->compareTo($price) == 0;
  }

  /**
   * Gets whether the current price is greater than the given price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is greater than the given price,
   *   FALSE otherwise.
   */
  public function greaterThan(PriceModified $price) {
    return $this->compareTo($price) == 1;
  }

  /**
   * Gets whether the current price is greater than or equal to the given price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is greater than or equal to the given price,
   *   FALSE otherwise.
   */
  public function greaterThanOrEqual(PriceModified $price) {
    return $this->greaterThan($price) || $this->equals($price);
  }

  /**
   * Gets whether the current price is lesser than the given price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is lesser than the given price,
   *   FALSE otherwise.
   */
  public function lessThan(PriceModified $price) {
    return $this->compareTo($price) == -1;
  }

  /**
   * Gets whether the current price is lesser than or equal to the given price.
   *
   * @param \Drupal\price\PriceModified $price
   *   The price.
   *
   * @return bool
   *   TRUE if the current price is lesser than or equal to the given price,
   *   FALSE otherwise.
   */
  public function lessThanOrEqual(PriceModified $price) {
    return $this->lessThan($price) || $this->equals($price);
  }

  /**
   * Asserts that the currency code is in the right format.
   *
   * Serves only as a basic sanity check.
   *
   * @param string $currency_code
   *   The currency code.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the currency code is not in the right format.
   */
  protected function assertCurrencyCodeFormat($currency_code) {
    if (strlen($currency_code) != '3') {
      throw new \InvalidArgumentException();
    }
  }

  /**
   * Asserts that the two prices have the same currency.
   *
   * @param \Drupal\price\PriceModified $first_price
   *   The first price.
   * @param \Drupal\price\PriceModified $second_price
   *   The second price.
   *
   * @throws \Drupal\price\Exception\CurrencyMismatchException
   *   Thrown when the prices do not have the same currency.
   */
  protected function assertSameCurrency(PriceModified $first_price, PriceModified $second_price) {
    if ($first_price->getCurrencyCode() != $second_price->getCurrencyCode()) {
      throw new CurrencyMismatchException();
    }
  }

}
