<?php

namespace Drupal\commerce_product_review\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the product review type entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_product_review_type",
 *   label = @Translation("Product review type"),
 *   label_collection = @Translation("Product review types"),
 *   label_singular = @Translation("product review type"),
 *   label_plural = @Translation("product review types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product review type",
 *     plural = "@count product review types",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\commerce_product_review\ProductReviewTypeStorage",
 *     "list_builder" = "Drupal\commerce_product_review\ProductReviewTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_product_review\Form\ProductReviewTypeForm",
 *       "edit" = "Drupal\commerce_product_review\Form\ProductReviewTypeForm",
 *       "delete" = "Drupal\commerce\Form\CommerceBundleEntityDeleteFormBase"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commerce_product_review_type",
 *   admin_permission = "administer commerce_product_review_type",
 *   bundle_of = "commerce_product_review",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "descriptionPlaceholder",
 *     "notificationEmail",
 *     "productTypes",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/product-review-types/add",
 *     "edit-form" = "/admin/commerce/config/product-review-types/{commerce_product_review_type}/edit",
 *     "delete-form" = "/admin/commerce/config/product-review-types/{commerce_product_review_type}/delete",
 *     "collection" = "/admin/commerce/config/product-review-types"
 *   }
 * )
 */
class ProductReviewType extends ConfigEntityBundleBase implements ProductReviewTypeInterface {

  /**
   * The product review type description.
   *
   * @var string
   */
  protected $description;

  /**
   * The description placeholder text.
   *
   * @var string
   */
  protected $descriptionPlaceholder;

  /**
   * The notification email address(es).
   *
   * @var string
   */
  protected $notificationEmail;

  /**
   * The matchting product type ID(s).
   *
   * @var string[]
   */
  protected $productTypes;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescriptionPlaceholder() {
    return $this->descriptionPlaceholder;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescriptionPlaceholder($description_placeholder) {
    $this->descriptionPlaceholder = $description_placeholder;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotificationEmail() {
    return $this->notificationEmail;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotificationEmail($notification_email) {
    $this->notificationEmail = $notification_email;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductTypeIds() {
    return $this->productTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductTypeIds(array $product_type_ids) {
    $this->productTypes = $product_type_ids;
    return $this;
  }

}
