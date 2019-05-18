<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce_price\Calculator;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Default product review manager implementation.
 */
class ProductReviewManager implements ProductReviewManagerInterface {

  /**
   * The product review storage.
   *
   * @var \Drupal\commerce_product_review\ProductReviewStorageInterface
   */
  protected $productReviewStorage;

  /**
   * Constructs a new ProductRatingManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->productReviewStorage = $entity_type_manager->getStorage('commerce_product_review');
  }

  /**
   * {@inheritdoc}
   */
  public function updateOverallRating(ProductInterface $product) {
    $reviews = $this->productReviewStorage->loadByProductId($product->id(), TRUE);
    $count = 0;
    $sum = '0.000';
    foreach ($reviews as $review) {
      $rating_value = (string) $review->getRatingValue();
      $sum = Calculator::add($sum, $rating_value, 3);
      $count++;
    }
    $avg = $count ? Calculator::divide($sum, (string) $count, 3) : $sum;
    if (!$product->get('overall_rating')->isEmpty()) {
      /** @var \Drupal\commerce_product_review\Plugin\Field\FieldType\OverallRatingItem $field_item */
      $field_item = $product->get('overall_rating')->first();
      /** @var \Drupal\commerce_product_review\OverallProductRating $rating_old */
      $rating_old = $field_item->toOverallProductRating();
      $needs_update = Calculator::compare($rating_old->getScore(), $avg) || Calculator::compare((string) $rating_old->getCount(), (string) $count);
    }
    else {
      $needs_update = TRUE;
    }

    if ($needs_update) {
      $rating = new OverallProductRating($avg, $count);
      $product->set('overall_rating', $rating);
      $product->save();
    }
    return OverallProductRating::fromProduct($product);
  }

  /**
   * {@inheritdoc}
   */
  public function getOverallRating(ProductInterface $product) {
    return OverallProductRating::fromProduct($product);
  }

}
