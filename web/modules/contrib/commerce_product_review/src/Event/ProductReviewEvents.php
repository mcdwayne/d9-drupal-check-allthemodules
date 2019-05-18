<?php

namespace Drupal\commerce_product_review\Event;

/**
 * Defines constants for product review related events.
 */
final class ProductReviewEvents {

  /**
   * Name of the event fired after loading a product review.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_review\Event\ProductReviewEvent
   */
  const PRODUCT_REVIEW_LOAD = 'commerce_product_review.commerce_product_review.load';

  /**
   * Name of the event fired after creating a new product review.
   *
   * Fired before the product review is saved.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_review\Event\ProductReviewEvent
   */
  const PRODUCT_REVIEW_CREATE = 'commerce_product_review.commerce_product_review.create';

  /**
   * Name of the event fired before saving a product review.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_review\Event\ProductReviewEvent
   */
  const PRODUCT_REVIEW_PRESAVE = 'commerce_product_review.commerce_product_review.presave';

  /**
   * Name of the event fired after saving a new product review.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_review\Event\ProductReviewEvent
   */
  const PRODUCT_REVIEW_INSERT = 'commerce_product_review.commerce_product_review.insert';

  /**
   * Name of the event fired after saving an existing product review.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_review\Event\ProductReviewEvent
   */
  const PRODUCT_REVIEW_UPDATE = 'commerce_product_review.commerce_product_review.update';

  /**
   * Name of the event fired before deleting a product review.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_review\Event\ProductReviewEvent
   */
  const PRODUCT_REVIEW_PREDELETE = 'commerce_product_review.commerce_product_review.predelete';

  /**
   * Name of the event fired after deleting a product review.
   *
   * @Event
   *
   * @see \Drupal\commerce_product_review\Event\ProductReviewEvent
   */
  const PRODUCT_REVIEW_DELETE = 'commerce_product_review.commerce_product_review.delete';

}
