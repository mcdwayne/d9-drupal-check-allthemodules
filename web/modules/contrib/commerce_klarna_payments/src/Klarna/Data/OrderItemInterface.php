<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to describe order items.
 */
interface OrderItemInterface extends ObjectInterface {

  /**
   * Sets the type.
   *
   * @param string $type
   *   The type.
   *
   * @see \Drupal\commerce_klarna_payments\Klarna\Data\OrderItemTypeInterface
   *
   * @return $this
   *   The self.
   */
  public function setType(string $type) : OrderItemInterface;

  /**
   * Gets the type.
   *
   * @return null|string
   *   The type.
   */
  public function getType() : ? string;

  /**
   * Sets the name.
   *
   * @param string $name
   *   The name.
   *
   * @return $this
   *   The self.
   */
  public function setName(string $name) : OrderItemInterface;

  /**
   * Gets the name.
   *
   * @return null|string
   *   The name.
   */
  public function getName() : ? string;

  /**
   * Sets the product URL.
   *
   * @param string $url
   *   The url.
   *
   * @return $this
   *   The self.
   */
  public function setProductUrl(string $url) : OrderItemInterface;

  /**
   * Gets the product url.
   *
   * @return null|string
   *   The product url.
   */
  public function getProductUrl() : ? string;

  /**
   * Sets the image URL.
   *
   * @param string $url
   *   The url.
   *
   * @return $this
   *   The self.
   */
  public function setImageUrl(string $url) : OrderItemInterface;

  /**
   * Gets the image URL.
   *
   * @return null|string
   *   The image url.
   */
  public function getImageUrl() : ? string;

  /**
   * Sets the quantity.
   *
   * @param int $quantity
   *   The quantity.
   *
   * @return $this
   *   The self.
   */
  public function setQuantity(int $quantity) : OrderItemInterface;

  /**
   * Gets the quantity.
   *
   * @return int
   *   The quantity.
   */
  public function getQuantity() : int;

  /**
   * Sets the quantity unit (like pcs, kg).
   *
   * @param string $unit
   *   The unit.
   *
   * @return $this
   *   The self.
   */
  public function setQuantityUnit(string $unit) : OrderItemInterface;

  /**
   * Gets the quantity unit (like pcs, kg).
   *
   * @return null|string
   *   The quantity unit.
   */
  public function getQuantityUnit() : ? string;

  /**
   * Sets he unit price (integer, 1000 = 10 €).
   *
   * Includes taxes, excludes discount.
   *
   * @param int $price
   *   The price.
   *
   * @return $this
   *   The self.
   */
  public function setUnitPrice(int $price) : OrderItemInterface;

  /**
   * Gets the unit price.
   *
   * @return int
   *   The unit price.
   */
  public function getUnitPrice() : int;

  /**
   * Sets the tax rate (1000 = 10%).
   *
   * @param int $rate
   *   The tax rate.
   *
   * @return $this
   *   The self.
   */
  public function setTaxRate(int $rate) : OrderItemInterface;

  /**
   * Gets the tax rate.
   *
   * @return int
   *   The tax rate.
   */
  public function getTaxRate() : int;

  /**
   * Sets the total tax amount.
   *
   * @param int $amount
   *   The total tax amount.
   *
   * @return $this
   *   The self.
   */
  public function setTotalTaxAmount(int $amount) : OrderItemInterface;

  /**
   * Gets the total tax amount.
   *
   * @return int
   *   The total tax amount.
   */
  public function getTotalTaxAmount() : int;

  /**
   * Sets the total amount.
   *
   * Includes taxes and discount (quantity * unit_price).
   *
   * @param int $amount
   *   The total amount.
   *
   * @return $this
   *   The self.
   */
  public function setTotalAmount(int $amount) : OrderItemInterface;

  /**
   * Gets the total amount.
   *
   * Includes taxes and discount (quantity * unit_price).
   *
   * @return int
   *   The total amount.
   */
  public function getTotalAmount() : int;

  /**
   * Sets the reference (SKU or similar).
   *
   * @param string $reference
   *   The reference.
   *
   * @return $this
   *   The self.
   */
  public function setReference(string $reference) : OrderItemInterface;

  /**
   * Gets the reference (SKU or similar).
   *
   * @return null|string
   *   The reference.
   */
  public function getReference() : ? string;

}
