<?php

namespace Drupal\commerce_addon\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Service Level Addon entity class.
 *
 * @ContentEntityType(
 *   id = "commerce_addon",
 *   label = @Translation("Addon"),
 *   label_collection = @Translation("Addons"),
 *   label_singular = @Translation("Addon"),
 *   label_plural = @Translation("Addons"),
 *   label_count = @PluralTranslation(
 *     singular = "@count addon",
 *     plural = "@count addons",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "access" = "Drupal\commerce\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\commerce\EntityPermissionProvider",
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_addon\AddonListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\commerce_addon\Form\AddonForm",
 *       "edit" = "Drupal\commerce_addon\Form\AddonForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   admin_permission = "administer commerce_addon",
 *   permission_granularity = "bundle",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   base_table = "commerce_addon",
 *   data_table = "commerce_addon_field_data",
 *   entity_keys = {
 *     "id" = "addon_id",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "status" = "status",
 *   },
 *   links = {
 *     "add-page" = "/admin/commerce/addons/add",
 *     "add-form" = "/admin/commerce/addons/add/{commerce_addon_type}",
 *     "edit-form" = "/admin/commerce/addons/{commerce_addon}/edit",
 *     "collection" = "/admin/commerce/addons",
 *     "delete-form" = "/admin/commerce/addons/{commerce_addon}/delete",
 *   },
 *   bundle_entity_type = "commerce_addon_type",
 *   field_ui_base_route = "entity.commerce_addon_type.edit_form"
 * )
 */
class Addon extends ContentEntityBase implements AddonInterface {

  use EntityChangedTrait;

  /**
   * Gets the stores through which the purchasable entity is sold.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface[]
   *   The stores.
   */
  public function getStores() {
    // Return all stores for the sake of simplicity.
    /** @var \Drupal\commerce_store\Entity\StoreInterface[] $stores */
    $stores = $this->entityTypeManager()->getStorage('commerce_store')->loadMultiple();
    return $stores;
  }

  /**
   * Gets the purchasable entity's order item type ID.
   *
   * @return string
   *   The order item type ID.
   */
  public function getOrderItemTypeId() {
    return 'addon';
  }

  /**
   * Gets the purchasable entity's order item title.
   *
   * Saved in the $order_item->title field to protect the order items of
   * completed orders against changes in the referenced purchased entity.
   *
   * @return string
   *   The order item title.
   */
  public function getOrderItemTitle() {
    return $this->label();
  }

  /**
   * Gets the purchasable entity's price.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The price, or NULL.
   */
  public function getPrice() {
    if (!$this->get('price')->isEmpty()) {
      return $this->get('price')->first()->toPrice();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if (!$this->get('description')->isEmpty()) {
      return $this->get('description')->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    $key = $this->getEntityType()->getKey('status');
    return (bool) $this->get($key)->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published = NULL) {
    $key = $this->getEntityType()->getKey('status');
    $this->set($key, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function setUnpublished() {
    $key = $this->getEntityType()->getKey('status');
    $this->set($key, FALSE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The service level addon title.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
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

    $fields['price'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Price'))
      ->setDescription(t('The service level addon price'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel('Description')
      ->setDescription('Enter the product description')
      ->setTranslatable(TRUE)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether the service level addon is active.'))
      ->setDefaultValue(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 99,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when the service level addon was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time when the service level addon was last edited.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

}
