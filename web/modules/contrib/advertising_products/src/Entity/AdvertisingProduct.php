<?php

namespace Drupal\advertising_products\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\advertising_products\AdvertisingProductInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Advertising Product entity.
 *
 * @ingroup advertising_products
 *
 * @ContentEntityType(
 *   id = "advertising_product",
 *   label = @Translation("Advertising Product"),
 *   bundle_label = @Translation("Advertising Product type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\advertising_products\AdvertisingProductListBuilder",
 *     "views_data" = "Drupal\advertising_products\Entity\AdvertisingProductViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\advertising_products\Form\AdvertisingProductForm",
 *       "add" = "Drupal\advertising_products\Form\AdvertisingProductForm",
 *       "edit" = "Drupal\advertising_products\Form\AdvertisingProductForm",
 *       "delete" = "Drupal\advertising_products\Form\AdvertisingProductDeleteForm",
 *     },
 *     "access" = "Drupal\advertising_products\AdvertisingProductAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\advertising_products\AdvertisingProductHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "advertising_product",
 *   admin_permission = "administer advertising product entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "product_name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/advertising_product/{advertising_product}",
 *     "add-form" = "/admin/structure/advertising_product/add/{advertising_product_type}",
 *     "edit-form" = "/admin/structure/advertising_product/{advertising_product}/edit",
 *     "delete-form" = "/admin/structure/advertising_product/{advertising_product}/delete",
 *     "collection" = "/admin/content/advertising_product",
 *   },
 *   bundle_entity_type = "advertising_product_type",
 *   field_ui_base_route = "entity.advertising_product_type.edit_form"
 * )
 */
class AdvertisingProduct extends ContentEntityBase implements AdvertisingProductInterface {
  use EntityChangedTrait;
  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
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
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Advertising Product entity.'))
      ->setReadOnly(TRUE);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The Advertising Product type/bundle.'))
      ->setSetting('target_type', 'advertising_product_type')
      ->setRequired(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Advertising Product entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Advertising Product entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the product.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -20,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -20,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Advertising Product is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'boolean',
        'weight' => 20,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_sold_out'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Sold out'))
      ->setDescription(t('A boolean indicating whether the Advertising Product is sold out or not.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'boolean',
        'weight' => 20,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'boolean',
        'weight' => 25,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Advertising Product entity.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['product_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the product.'))
      ->setSettings(array(
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -19,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => -19,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product ID'))
      ->setDescription(t('The ID of the product.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -18,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -18,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Image'))
      ->setDescription(t('The image of the product.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'image',
        'weight' => -17,
        'label' => 'hidden',
        'settings' => array(
          'image_style' => 'thumbnail',
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'image_image',
        'weight' => -17,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Price'))
      ->setDescription(t('The current price of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => -16,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -16,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_original_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Original price'))
      ->setDescription(t('The original price of the product.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'number_decimal',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('The currency the product price is in. This must be a 3-letter code from the ISO 4217 list of currency codes ("EUR", "USD" etc.).'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('EUR')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -15,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -15,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_brand'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Brand'))
      ->setDescription(t('The brand of the product.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -14,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -14,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Detail page url'))
      ->setDescription(t('The detail page url of the product.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'link',
        'weight' => -13,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'link_default',
        'weight' => -13,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_shop'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Shop'))
      ->setDescription(t('The shop where the product is being sold'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -12,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -12,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['product_provider'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product provider'))
      ->setDescription(t('Product provider plugin ID'));

    return $fields;
  }

}
