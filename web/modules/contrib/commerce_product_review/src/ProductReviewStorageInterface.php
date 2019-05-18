<?php

namespace Drupal\commerce_product_review;

use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Defines the interface for product review storage classes.
 */
interface ProductReviewStorageInterface extends SqlEntityStorageInterface {

  /**
   * Load product reviews by product.
   *
   * @param int $product_id
   *   The product ID.
   * @param bool $only_active
   *   Whether to only load active reviews. Defaults to TRUE.
   *
   * @return \Drupal\commerce_product_review\Entity\ProductReviewInterface[]
   *   An array of product reviews of the given product.
   */
  public function loadByProductId($product_id, $only_active = TRUE);

  /**
   * Load product reviews by product and user.
   *
   * @param int $product_id
   *   The product ID.
   * @param int $user_id
   *   The user ID.
   *
   * @return \Drupal\commerce_product_review\Entity\ProductReviewInterface[]
   *   An array of product reviews of the given product and user.
   */
  public function loadByProductAndUser($product_id, $user_id);

}
