<?php

namespace Drupal\commerce_add_to_cart_link;

use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Provides the cart link token interface.
 *
 * This service is responsible for both generating and validating tokens that
 * are added to the cart links. The tokens are tied to the user session.
 */
interface CartLinkTokenInterface {

  /**
   * Generates a token for the given product variation.
   *
   * The token is added to the add to cart link and tied to the user session.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return string
   *   The generated token.
   */
  public function generate(ProductVariationInterface $variation);

  /**
   * Checks the given token for the given variation for validity.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param string $token
   *   The token to be validated.
   *
   * @return bool
   *   TRUE, if the given token is valid, FALSE otherwise.
   */
  public function validate(ProductVariationInterface $variation, $token);

}
