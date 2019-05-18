<?php

namespace Drupal\contacts_events\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Price;

/**
 * An interface describing purchasable entities which are single use.
 *
 * These are attached to the order item and track prices and mapping themselves,
 * usually for ease of reporting.
 */
interface SingleUsePurchasableEntityInterface extends PurchasableEntityInterface {

  /**
   * Get the order item this entity belongs to.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface|null
   *   The order item.
   */
  public function getOrderItem();

  /**
   * Set the order item this entity belongs to.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item this entity belongs to.
   *
   * @return $this
   */
  public function setOrderItem(OrderItemInterface $order_item);

  /**
   * Get the mapping for the price calculation.
   *
   * @return array|null
   *   The mapped price field value array or NULL if there is none.
   */
  public function getMappedPrice();

  /**
   * Set the mapping for the price calculation.
   *
   * @param array $mapped_price
   *   The mapped price field value array.
   *
   * @return $this
   */
  public function setMappedPrice(array $mapped_price);

  /**
   * Get the price override, if any.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The price override, or NULL if not overridden or N/A.
   */
  public function getPriceOverride();

  /**
   * Get the calculated price (ignoring overrides) for the entity.
   *
   * @return \Drupal\commerce_price\Price
   *   The calculated price.
   */
  public function getCalculatedPrice();

  /**
   * Set the calculated price for the entity..
   *
   * @param \Drupal\commerce_price\Price|null $price
   *   The calculated price or NULL to clear it.
   *
   * @return $this
   */
  public function setCalculatedPrice(Price $price = NULL);

}
