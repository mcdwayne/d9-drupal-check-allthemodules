<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Defines the product review manager interface.
 */
interface ProductReviewManagerInterface {

  /**
   * Updates the rating statistics of the given product.
   *
   * The 'overall_rating' field of the product will be fully recalculated.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product to update.
   *
   * @return \Drupal\commerce_product_review\OverallProductRating
   *   An overall product rating value object.
   */
  public function updateOverallRating(ProductInterface $product);

  /**
   * Returns the rating statistics of the given product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product to update.
   *
   * @return \Drupal\commerce_product_review\OverallProductRating
   *   An overall product rating value object.
   */
  public function getOverallRating(ProductInterface $product);

}
