<?php

namespace Drupal\commerce_product_review\Entity;

use Drupal\commerce\Entity\CommerceContentEntityBase;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Annotation\PluralTranslation;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionLogEntityTrait;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the product review entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_product_review",
 *   label = @Translation("Product review"),
 *   label_collection = @Translation("Product reviews"),
 *   label_singular = @Translation("product review"),
 *   label_plural = @Translation("product reviews"),
 *   label_count = @PluralTranslation(
 *     singular = "@count product review",
 *     plural = "@count product reviews",
 *   ),
 *   bundle_label = @Translation("Product review type"),
 *   handlers = {
 *     "event" = "Drupal\commerce_product_review\Event\ProductReviewEvent",
 *     "storage" = "Drupal\commerce_product_review\ProductReviewStorage",
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_product_review\ProductReviewListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\commerce_product_review\Form\ProductReviewForm",
 *       "add" = "Drupal\commerce_product_review\Form\ProductReviewForm",
 *       "edit" = "Drupal\commerce_product_review\Form\ProductReviewForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_product_review",
 *   permission_granularity = "bundle",
 *   fieldable = TRUE,
 *   base_table = "commerce_product_review",
 *   data_table = "commerce_product_review_field_data",
 *   revision_table = "commerce_product_review_revision",
 *   revision_data_table = "commerce_product_review_revision_field_data",
 *   entity_keys = {
 *     "id" = "review_id",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   links = {
 *     "edit-form" = "/product-review/{commerce_product_review}/edit",
 *     "delete-form" = "/product-review/{commerce_product_review}/delete",
 *     "collection" = "/admin/commerce/product-reviews"
 *   },
 *   bundle_entity_type = "commerce_product_review_type",
 *   field_ui_base_route = "entity.commerce_product_review_type.edit_form",
 * )
 */
class ProductReview extends CommerceContentEntityBase implements ProductReviewInterface, RevisionLogInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;
  use RevisionLogEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPublishedAs() {
    return $this->get('published_as')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublishedAs($published_as) {
    $this->set('published_as', $published_as);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProduct() {
    return $this->getTranslatedReferencedEntity('product_id');
  }

  /**
   * {@inheritdoc}
   */
  public function setProduct(ProductInterface $product) {
    $this->set('product_id', $product->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductId() {
    return $this->get('product_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setProductId($product_id) {
    $this->set('product_id', $product_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRatingValue() {
    return $this->get('rating_value')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRatingValue($rating_value) {
    $this->set('rating_value', $rating_value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    $fields += static::revisionLogBaseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The product review author.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\commerce_product_review\Entity\ProductReview::getCurrentUserId')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('Title or summary of the product review.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['published_as'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Published as'))
      ->setDescription(t('Your name, which is displayed publicly.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product'))
      ->setDescription(t('The reviewed product.'))
      ->setSetting('target_type', 'commerce_product')
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Write a review here.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['rating_value'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Rating value'))
      ->setDescription(t('The rating value.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', [
        'type' => 'commerce_product_review_star_rating',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'type' => 'commerce_product_review_single_rating_stars',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status']
      ->setLabel(t('Published'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 90,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the product review was created.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the product review was last edited.'));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
