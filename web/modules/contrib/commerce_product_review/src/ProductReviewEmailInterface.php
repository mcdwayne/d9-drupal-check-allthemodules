<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce_product_review\Entity\ProductReviewInterface;

/**
 * Defines the interface for product review e-mail services.
 */
interface ProductReviewEmailInterface {

  /**
   * Sends an e-mail notification to the configured addresses.
   *
   * The recipient(s) can be configured per product review type. If no recipient
   * is defined for the given review's bundle, no notification will be sent and
   * an empty array returned.
   *
   * @param \Drupal\commerce_product_review\Entity\ProductReviewInterface $review
   *   The product review entity.
   *
   * @return array
   *   The e-mail message as returned from mail manager. If the review's bundle
   *   is not configured to send e-mail notifications, always an empty array
   *   will be returned.
   */
  public function sendNotification(ProductReviewInterface $review);

}
