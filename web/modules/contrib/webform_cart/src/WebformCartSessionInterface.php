<?php

namespace Drupal\webform_cart;

/**
 * Interface WebformCartSessionInterface.
 */
interface WebformCartSessionInterface {

  // The cart session types.
  const ACTIVE = 'active';
  const COMPLETED = 'completed';

  /**
   * Gets all cart order ids from the session.
   *
   * @param string $type
   *   The cart session type.
   *
   * @return int[]
   *   A list of cart orders ids.
   */
  public function getCartIds($type = self::ACTIVE);

  /**
   * Adds the given cart order ID to the session.
   *
   * @param int $cart_id
   *   The cart order ID.
   * @param string $type
   *   The cart session type.
   */
  public function addCartId($cart_id, $type = self::ACTIVE);

  /**
   * Checks whether the given cart order ID exists in the session.
   *
   * @param int $cart_id
   *   The cart order ID.
   * @param string $type
   *   The cart session type.
   *
   * @return bool
   *   TRUE if the given cart order ID exists in the session, FALSE otherwise.
   */
  public function hasCartId($cart_id, $type = self::ACTIVE);

  /**
   * Deletes the given cart order id from the session.
   *
   * @param int $cart_id
   *   The cart order ID.
   * @param string $type
   *   The cart session type.
   */
  public function deleteCartId($cart_id, $type = self::ACTIVE);

}
