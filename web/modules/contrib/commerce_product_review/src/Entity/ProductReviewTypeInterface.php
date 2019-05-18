<?php

namespace Drupal\commerce_product_review\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Defines the interface for product types.
 */
interface ProductReviewTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the notification email address(es).
   *
   * If provided, this email address(es) will receive a notification, when a
   * review of this type was created.
   *
   * @return string
   *   The notification email address(es).
   */
  public function getNotificationEmail();

  /**
   * Sets the notification email address(es).
   *
   * Multiple addresses must be separated by comma.
   *
   * @param string $notification_email
   *   The notification email address(es).
   *
   * @return $this
   */
  public function setNotificationEmail($notification_email);

  /**
   * Gets the matching product type IDs.
   *
   * @return string[]
   *   The product type IDs.
   */
  public function getProductTypeIds();

  /**
   * Sets the matching product type IDs.
   *
   * @param string[] $product_type_ids
   *   The matching product type IDs.
   *
   * @return $this
   */
  public function setProductTypeIds(array $product_type_ids);

  /**
   * Gets the description placeholder text.
   *
   * @return string
   *   The description placeholder text.
   */
  public function getDescriptionPlaceholder();

  /**
   * Sets the description placeholder text.
   *
   * @param string $description
   *   The description placeholder text.
   *
   * @return $this
   */
  public function setDescriptionPlaceholder($description);

}
