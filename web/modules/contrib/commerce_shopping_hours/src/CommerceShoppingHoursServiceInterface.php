<?php

namespace Drupal\commerce_shopping_hours;

/**
 * Interface CommerceShoppingHoursServiceInterface.
 *
 * @package Drupal\commerce_shopping_hours
 */
interface CommerceShoppingHoursServiceInterface {

  /**
   * Check to see if shop is open.
   */
  public function isShopOpen();

  /**
   * Get shopping hours.
   */
  public function getShoppingHours();

}
