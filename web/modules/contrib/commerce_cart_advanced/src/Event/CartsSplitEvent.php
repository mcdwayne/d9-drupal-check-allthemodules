<?php

namespace Drupal\commerce_cart_advanced\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for splitting carts to current and non current.
 *
 * @see \Drupal\commerce_cart_advanced\Event\CartEvents
 */
class CartsSplitEvent extends Event {

  /**
   * The carts.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface[]
   */
  protected $carts;

  /**
   * The current carts.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface[]
   */
  protected $currentCarts;

  /**
   * The non current carts.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface[]
   */
  protected $nonCurrentCarts;

  /**
   * Constructs a new CartAdvancedNonCurrentEvent.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $carts
   *   The carts.
   */
  public function __construct(array $carts) {
    $this->carts = $carts;
    $this->currentCarts = [];
    $this->nonCurrentCarts = [];
  }

  /**
   * Gets all carts.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface[]
   *   The array with carts.
   */
  public function getCarts() {
    return $this->carts;
  }

  /**
   * Gets the current carts.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface[]
   *   The array with current carts.
   */
  public function getCurrentCarts() {
    return $this->currentCarts;
  }

  /**
   * Sets the current carts.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $carts
   *   The array of carts.
   */
  public function setCurrentCarts(array $carts) {
    $this->currentCarts = $carts;
  }

  /**
   * Gets the non current carts.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface[]
   *   The array with non-current carts.
   */
  public function getNonCurrentCarts() {
    return $this->nonCurrentCarts;
  }

  /**
   * Sets the non current carts.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface[] $carts
   *   The array of carts.
   */
  public function setNonCurrentCarts(array $carts) {
    $this->nonCurrentCarts = $carts;
  }

}
