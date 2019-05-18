<?php

namespace Drupal\commerce_cart_refresh;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\views\Form\ViewsForm;

/**
 * An interface defining a product manager.
 */
interface CommerceCartRefreshManagerInterface {

  /**
   * Get the DOM element with Price amount.
   *
   * Helper for Ajax callbacks.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The selected product variation.
   */
  public function getPriceDomSelector(ProductVariationInterface $variation);

  /**
   * Check that a variation is available for today.
   *
   * @param int $quantity
   *   The selected quantity.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The selected product variation.
   */
  public function getCalculatedPrice(int $quantity, ProductVariationInterface $variation);

}
