<?php

namespace Drupal\uc_cart;

/**
 * Represents a shopping cart.
 */
interface CartInterface {

  /**
   * Time in seconds after which a cart order is deemed abandoned.
   *
   * Default is 86400 seconds / 24 hours.
   */
  const ORDER_TIMEOUT = 86400;

  /**
   * Time in seconds after which the checkout page is deemed abandoned.
   *
   * Default is 1800 seconds / 30 minutes.
   */
  const CHECKOUT_TIMEOUT = 1800;

  /**
   * Returns the unique ID for the cart.
   *
   * @return string
   *   The cart ID.
   */
  public function getId();

  /**
   * Returns the items in the shopping cart.
   *
   * @return \Drupal\uc_cart\CartItemInterface[]
   *   The items.
   */
  public function getContents();

  /**
   * Adds an item to the cart.
   *
   * @param int $nid
   *   Node ID to add to cart.
   * @param int $qty
   *   Quantity to add to cart.
   * @param array $data
   *   Array of module-specific data to add to cart.
   * @param bool $msg
   *   Whether to display a message upon adding an item to the cart.
   *
   * @return \Drupal\Core\Url
   *   A URL to redirect to.
   */
  public function addItem($nid, $qty = 1, array $data = NULL, $msg = TRUE);

  /**
   * Empties a cart of its contents.
   */
  public function emptyCart();

  /**
   * Determines whether a cart contains shippable items or not.
   *
   * @return bool
   *   TRUE if the cart contains at least one shippable item, FALSE otherwise.
   */
  public function isShippable();

}
