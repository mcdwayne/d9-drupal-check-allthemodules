<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * The default storage for product review type entities.
 *
 * @see \Drupal\commerce_product_review\Entity\ProductReviewTypeInterface
 */
class ProductReviewTypeStorage extends ConfigEntityStorage implements ProductReviewTypeStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function findMatchingReviewType(ProductInterface $product) {
    $review_types = $this->loadMultiple();
    /** @var \Drupal\commerce_product_review\Entity\ProductReviewTypeInterface $review_type */
    foreach ($review_types as $review_type) {
      if (in_array($product->bundle(), $review_type->getProductTypeIds())) {
        return $review_type;
      }
    }
    return NULL;
  }

}
