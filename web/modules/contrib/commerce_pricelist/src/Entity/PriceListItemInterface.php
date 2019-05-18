<?php

namespace Drupal\commerce_pricelist\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Defines the interface for price list items.
 */
interface PriceListItemInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the parent price list.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListInterface
   *   The price list.
   */
  public function getPriceList();

  /**
   * Gets the parent price list ID.
   *
   * @return int
   *   The price list ID.
   */
  public function getPriceListId();

  /**
   * Gets the purchasable entity.
   *
   * @return \Drupal\commerce\PurchasableEntityInterface|null
   *   The purchasable entity, or NULL.
   */
  public function getPurchasableEntity();

  /**
   * Sets the purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $purchasable_entity
   *   The purchasable entity.
   *
   * @return $this
   */
  public function setPurchasableEntity(PurchasableEntityInterface $purchasable_entity);

  /**
   * Gets the purchasable entity ID.
   *
   * @return int
   *   The purchasable entity ID.
   */
  public function getPurchasableEntityId();

  /**
   * Sets the purchasable entity ID.
   *
   * @param string $purchasable_entity_id
   *   The purchasable entity ID.
   *
   * @return $this
   */
  public function setPurchasableEntityId($purchasable_entity_id);

  /**
   * Gets the quantity.
   *
   * Represents a quantity tier.
   * For example, if the price list has items for 10, 50, and 100 products,
   * and the customer orders 20, they will get the price for 10.
   *
   * @return string
   *   The quantity.
   */
  public function getQuantity();

  /**
   * Sets the quantity.
   *
   * @param string $quantity
   *   The quantity.
   *
   * @return $this
   */
  public function setQuantity($quantity);

  /**
   * Gets the list price.
   *
   * @return \Drupal\commerce_price\Price
   *   The list price.
   */
  public function getListPrice();

  /**
   * Sets the list price.
   *
   * @param \Drupal\commerce_price\Price $list_price
   *   The list price.
   *
   * @return $this
   */
  public function setListPrice(Price $list_price);

  /**
   * Gets the price.
   *
   * @return \Drupal\commerce_price\Price
   *   The price.
   */
  public function getPrice();

  /**
   * Sets the price.
   *
   * @param \Drupal\commerce_price\Price $price
   *   The price.
   *
   * @return $this
   */
  public function setPrice(Price $price);

  /**
   * Get whether the price list item is enabled.
   *
   * @return bool
   *   TRUE if the price list item is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets whether the price list item is enabled.
   *
   * @param bool $enabled
   *   Whether the price list item is enabled.
   *
   * @return $this
   */
  public function setEnabled($enabled);

}
