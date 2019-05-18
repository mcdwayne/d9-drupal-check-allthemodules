<?php

namespace Drupal\commerce_product_review\Entity;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines the interface for product reviews.
 */
interface ProductReviewInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Gets the product review title/summary.
   *
   * @return string
   *   The product review title/summary.
   */
  public function getTitle();

  /**
   * Sets the product review title/summary.
   *
   * @param string $title
   *   The product review title/summary.
   *
   * @return $this
   */
  public function setTitle($title);

  /**
   * Gets the review's author public visible name.
   *
   * @return string
   *   The review's author public visible name.
   */
  public function getPublishedAs();

  /**
   * Sets the review's author public visible name.
   *
   * @param string $published_as
   *   The review's author public visible name.
   *
   * @return $this
   */
  public function setPublishedAs($published_as);

  /**
   * Gets the reviewed product.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The product entity.
   */
  public function getProduct();

  /**
   * Sets the reviewed product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product entity.
   *
   * @return $this
   */
  public function setProduct(ProductInterface $product);

  /**
   * Gets the reviewed product ID.
   *
   * @return int
   *   The reviewed product ID.
   */
  public function getProductId();

  /**
   * Sets the reviewed product ID.
   *
   * @param int $product_id
   *   The reviewed product id.
   *
   * @return $this
   */
  public function setProductId($product_id);

  /**
   * Gets the product review text.
   *
   * @return string
   *   The product review text.
   */
  public function getDescription();

  /**
   * Sets the product review description text.
   *
   * @param string $description
   *   The product review description text.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the rating value.
   *
   * @return int
   *   The rating value.
   */
  public function getRatingValue();

  /**
   * Sets the rating value.
   *
   * @param int $rating_value
   *   The rating value.
   *
   * @return $this
   */
  public function setRatingValue($rating_value);

  /**
   * Gets the product creation timestamp.
   *
   * @return int
   *   The product creation timestamp.
   */
  public function getCreatedTime();

  /**
   * Sets the product creation timestamp.
   *
   * @param int $timestamp
   *   The product creation timestamp.
   *
   * @return $this
   */
  public function setCreatedTime($timestamp);

}
