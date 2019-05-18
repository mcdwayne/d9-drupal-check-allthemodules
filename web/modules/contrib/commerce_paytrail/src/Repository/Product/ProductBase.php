<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Repository\Product;

use Drupal\commerce_paytrail\AssertTrait;
use Drupal\commerce_price\Price;
use Webmozart\Assert\Assert;

/**
 * Provides an object for Paytrail product handling.
 */
abstract class ProductBase {

  use AssertTrait;

  /**
   * The product title.
   *
   * @var string
   */
  protected $title;

  /**
   * The product quantity.
   *
   * @var int
   */
  protected $quantity;

  /**
   * The price.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $price;

  /**
   * The tax amount.
   *
   * @var float
   */
  protected $tax = 0.00;

  /**
   * The discount amount.
   *
   * @var int
   */
  protected $discount = 0;

  /**
   * The product id.
   *
   * @var string
   */
  protected $id;

  /**
   * Builds item form array.
   *
   * @param int $index
   *   The product index.
   *
   * @return array
   *   The build array.
   */
  public function build(int $index) : array {
    $values = [
      sprintf('ITEM_TITLE[%d]', $index) => $this->getTitle(),
      sprintf('ITEM_QUANTITY[%d]', $index) => $this->getQuantity(),
      sprintf('ITEM_UNIT_PRICE[%d]', $index) => $this->getPrice(),
      sprintf('ITEM_VAT_PERCENT[%d]', $index) => $this->getTax(),
      sprintf('ITEM_DISCOUNT_PERCENT[%d]', $index) => $this->getDiscount(),
      sprintf('ITEM_TYPE[%d]', $index) => $this->getType(),
    ];

    if ($this->getItemId()) {
      $values[sprintf('ITEM_ID[%d]', $index)] = $this->getItemId();
    }
    return $values;
  }

  /**
   * Sets the item id.
   *
   * @param string $id
   *   The item id.
   *
   * @return $this
   *   The self.
   */
  public function setItemId(string $id) : self {
    Assert::numeric($id);
    Assert::maxLength($id, 16);

    $this->id = $id;

    return $this;
  }

  /**
   * Sets the product title.
   *
   * Note: Paytrail has strict validation for this field. You might want to
   * strip non-allowed characters from this field to avoid validation errors.
   *
   * See: SanitizeTrait::sanitize().
   *
   * @param string $title
   *   The title.
   *
   * @return $this
   *   The self.
   */
  public function setTitle(string $title) : self {
    $this->assertText($title);

    $this->title = $title;
    return $this;
  }

  /**
   * Gets the product title.
   *
   * @return string
   *   The product title.
   */
  public function getTitle() : string {
    return $this->title;
  }

  /**
   * Sets the product quantity.
   *
   * @param int $quantity
   *   The quantity.
   *
   * @return $this
   *   The self.
   */
  public function setQuantity(int $quantity) : self {
    $this->quantity = $quantity;
    return $this;
  }

  /**
   * Gets the product quantity.
   *
   * @return int
   *   The product quantity.
   */
  public function getQuantity() : int {
    return (int) round($this->quantity);
  }

  /**
   * Sets the product price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return $this
   *   The self.
   */
  public function setPrice(Price $price) : self {
    Assert::oneOf($price->getCurrencyCode(), ['EUR']);
    $this->assertAmountBetween($price, 0, 499999);

    $this->price = $price;
    return $this;
  }

  /**
   * Gets the product price.
   *
   * @return string
   *   The formatted price.
   */
  public function getPrice() : string {
    return $this->formatPrice((float) $this->price->getNumber());
  }

  /**
   * Sets the product tax.
   *
   * @param float $tax
   *   The tax.
   *
   * @return $this
   *   The self.
   */
  public function setTax(float $tax) : self {
    $this->assertBetween($tax, 0, 100);

    $this->tax = $tax;
    return $this;
  }

  /**
   * Gets the formatted tax.
   *
   * @return string
   *   The formatted tax.
   */
  public function getTax() : string {
    return $this->formatPrice($this->tax);
  }

  /**
   * Sets the discount.
   *
   * @param float $discount
   *   The discount.
   *
   * @return $this
   *   The self.
   */
  public function setDiscount(float $discount) : self {
    $this->assertBetween($discount, 0, 100);

    $this->discount = $discount;
    return $this;
  }

  /**
   * Gets the discount.
   *
   * @return float
   *   The discount amount.
   */
  public function getDiscount() : float {
    return $this->discount;
  }

  /**
   * Gets the product type.
   *
   * @return int
   *   The product type.
   */
  abstract public function getType() : int;

  /**
   * Gets the product id.
   *
   * @return string
   *   The number.
   */
  public function getItemId() : string {
    return $this->id;
  }

  /**
   * Formats the price.
   *
   * @param float $price
   *   The price.
   *
   * @return string
   *   Formatted price component.
   */
  protected function formatPrice(float $price) : string {
    return number_format($price, 2, '.', '');
  }

}
