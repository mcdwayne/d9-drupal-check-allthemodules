<?php

namespace Drupal\webform_cart;

/**
 * Interface WebformCartInterface.
 */
interface WebformCartInterface {

  /**
   * @return mixed
   */
  public function getCart();

  /**
   * @param $order_item
   *
   * @return mixed
   */
  public function setCart($orderItem);

  /**
   * @return mixed
   */
  public function getCheckout();

  /**
   * @return mixed
   */
  public function removeItem($itemId);

  /**
   * @param $orderItem
   *
   * @return mixed
   */
  public function updateQuantity($orderItem);

    /**
   * @return mixed
   */
  public function getCount();

  /**
   * @param $destination
   *
   * @return mixed
   */
  public function setDestination($destination);

}
