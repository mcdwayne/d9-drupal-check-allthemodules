<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Klarna\Data;

/**
 * An interface to describe order item product identifiers.
 */
interface ProductIdentifierInterface extends ObjectInterface {

  /**
   * Sets the category path.
   *
   * The product's category path as used in the merchant's
   * webshop. Include the full and most detailed category and
   * separate the segments with ' > '.
   *
   * @param string $path
   *   The path.
   *
   * @return $this
   *   The self.
   */
  public function setCategoryPath(string $path) : ProductIdentifierInterface;

  /**
   * Sets the global trade item number.
   *
   * The product's Global Trade Item Number (GTIN). Common types
   * of GTIN are EAN, ISBN or UPC. Exclude dashes and spaces, where possible.
   *
   * @param string $number
   *   The trade item number.
   *
   * @return $this
   *   The self.
   */
  public function setGlobalTradeItemNumber(string $number) : ProductIdentifierInterface;

  /**
   * Sets the manufacturer part number.
   *
   * The product's Manufacturer Part Number (MPN), which - together
   * with the brand - uniquely identifies a product. Only submit MPNs
   * assigned by a manufacturer and use the most specific MPN possible.
   *
   * @param string $number
   *   The manufacturer part number.
   *
   * @return $this
   *   The self.
   */
  public function setManufacturerPartNumber(string $number) : ProductIdentifierInterface;

  /**
   * Sets the brand name.
   *
   * The product's brand name as generally recognized by consumers.
   * If no brand is available for a product, do not supply any value.
   *
   * @param string $name
   *   The brand name.
   *
   * @return $this
   *   The self.
   */
  public function setBrandName(string $name) : ProductIdentifierInterface;

}
