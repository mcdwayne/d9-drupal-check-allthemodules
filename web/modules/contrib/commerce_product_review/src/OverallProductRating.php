<?php

namespace Drupal\commerce_product_review;

use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Value object for a convenient usage of product entity overall rating values.
 */
final class OverallProductRating {

  /**
   * The product to which the rating values are referencing to.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * A bcmath compatible decimal string representing the overall rating score.
   *
   * @var string
   */
  protected $score;

  /**
   * The number of ratings of the given product.
   *
   * @var int
   */
  protected $count;

  /**
   * OverallProductRating constructor.
   *
   * @param string $score
   *   The overall rating value.
   * @param int $count
   *   The rating count.
   * @param \Drupal\commerce_product\Entity\ProductInterface|null $product
   *   The product.
   */
  public function __construct($score, $count, ProductInterface $product = NULL) {
    $this->score = $score;
    $this->count = $count;
    $this->product = $product;
  }

  /**
   * Factory method allowing the object to be instantiated by product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product (optional).
   *
   * @return static
   *   A new OverallProductRating object based on the given product.
   */
  public static function fromProduct(ProductInterface $product) {
    if (!$product->get('overall_rating')->isEmpty()) {
      $score = $product->overall_rating->score;
      $count = $product->overall_rating->count;
    }
    else {
      $score = '0.0';
      $count = 0;
    }
    return new static($score, $count, $product);
  }

  /**
   * Gets the product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The product. Can be NULL in case, it was instantiated without product
   *   reference.
   */
  public function getProduct() {
    return $this->product;
  }

  /**
   * Gets the product's overall rating.
   *
   * @return string
   *   A bcmath compatible decimal string representing the overall rating score.
   */
  public function getScore() {
    return $this->score;
  }

  /**
   * Gets the rating count.
   *
   * @return int
   *   The number of ratings of the given product.
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * Gets the array representation of the overall rating object.
   *
   * @return array
   *   The array representation of the overall rating object. Please note, that
   *   the product reference won't be included here.
   */
  public function toArray() {
    return [
      'score' => $this->score,
      'count' => $this->count,
    ];
  }

}
