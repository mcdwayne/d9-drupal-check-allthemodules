<?php

namespace Drupal\commerce_product_review\EventSubscriber;

use Drupal\commerce_product_review\Event\ProductReviewEvent;
use Drupal\commerce_product_review\Event\ProductReviewEvents;
use Drupal\commerce_product_review\ProductReviewEmailInterface;
use Drupal\commerce_product_review\ProductReviewManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates product rating statistics on product review CUD events.
 */
class ProductReviewEventSubscriber implements EventSubscriberInterface {

  /**
   * The product review e-mail service.
   *
   * @var \Drupal\commerce_product_review\ProductReviewEmailInterface
   */
  protected $productReviewEmail;

  /**
   * The product review manager.
   *
   * @var \Drupal\commerce_product_review\ProductReviewManagerInterface
   */
  protected $productReviewManager;

  /**
   * Constructs a new ProductReviewEventSubscriber object.
   *
   * @param \Drupal\commerce_product_review\ProductReviewEmailInterface $product_review_email
   *   The product review e-mail service.
   * @param \Drupal\commerce_product_review\ProductReviewManagerInterface $product_rating_manager
   *   The product review manager.
   */
  public function __construct(ProductReviewEmailInterface $product_review_email, ProductReviewManagerInterface $product_rating_manager) {
    $this->productReviewEmail = $product_review_email;
    $this->productReviewManager = $product_rating_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      ProductReviewEvents::PRODUCT_REVIEW_INSERT => [
        ['updateOverallRating', 0],
        ['sendEmailNotification', -100],
      ],
      ProductReviewEvents::PRODUCT_REVIEW_DELETE => 'updateOverallRating',
      ProductReviewEvents::PRODUCT_REVIEW_UPDATE => 'updateOverallRating',
    ];
    return $events;
  }

  /**
   * Updates product rating statistics on product review CUD events.
   *
   * @param \Drupal\commerce_product_review\Event\ProductReviewEvent $event
   *   The product review event.
   */
  public function updateOverallRating(ProductReviewEvent $event) {
    $product = $event->getProductReview()->getProduct();
    if (empty($product)) {
      return;
    }

    $this->productReviewManager->updateOverallRating($product);
  }

  /**
   * Sends an e-mail notification after saving a new product review.
   *
   * The recipient(s) can be configured per product review type. If no recipient
   * is defined for the given review's bundle, no notification will be sent.
   *
   * @param \Drupal\commerce_product_review\Event\ProductReviewEvent $event
   *   The product review event.
   */
  public function sendEmailNotification(ProductReviewEvent $event) {
    $this->productReviewEmail->sendNotification($event->getProductReview());
  }

}
