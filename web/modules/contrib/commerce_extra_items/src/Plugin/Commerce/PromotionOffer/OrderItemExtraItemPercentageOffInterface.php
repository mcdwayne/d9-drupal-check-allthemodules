<?php

namespace Drupal\commerce_extra_items\Plugin\Commerce\PromotionOffer;

/**
 * Defines the interface for OrderItemExtraItemsPercentageOff class.
 */
interface OrderItemExtraItemPercentageOffInterface {

  /**
   * Gets the percentage.
   *
   * @return string
   *   The percentage.
   */
  public function getPercentage();

}
