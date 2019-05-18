<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce\CommerceContentEntityStorage;

/**
 * The default storage for product review entities.
 *
 * @see \Drupal\commerce_product_review\Entity\ProductReviewInterface
 */
class ProductReviewStorage extends CommerceContentEntityStorage implements ProductReviewStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function loadByProductId($product_id, $only_active = TRUE) {
    $query = $this->getQuery()->condition('product_id', $product_id);
    if ($only_active) {
      $query->condition('status', 1);
    }
    return $this->loadMultiple($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProductAndUser($product_id, $user_id) {
    $query = $this->getQuery()->condition('product_id', $product_id)->condition('uid', $user_id);
    return $this->loadMultiple($query->execute());
  }

}
