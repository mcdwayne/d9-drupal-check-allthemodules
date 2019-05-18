<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines the interface for product review type storage classes.
 */
interface ProductReviewTypeStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Finds the matching review type for the given product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return \Drupal\commerce_product_review\Entity\ProductReviewTypeInterface|null
   *   The review type or NULL.
   */
  public function findMatchingReviewType(ProductInterface $product);

}
