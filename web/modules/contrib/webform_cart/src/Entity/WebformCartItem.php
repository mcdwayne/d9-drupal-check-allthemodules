<?php

namespace Drupal\webform_cart\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Webform cart item entity entity.
 *
 * @ingroup webform_cart
 *
 * @ContentEntityType(
 *   id = "webform_cart_item",
 *   label = @Translation("Webform cart item entity"),
 *   bundle_label = @Translation("Webform cart item entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webform_cart\WebformCartItemListBuilder",
 *     "views_data" = "Drupal\webform_cart\Entity\WebformCartItemEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\webform_cart\Form\WebformCartItemForm",
 *       "add" = "Drupal\webform_cart\Form\WebformCartItemForm",
 *       "edit" = "Drupal\webform_cart\Form\WebformCartItemForm",
 *       "delete" = "Drupal\webform_cart\Form\WebformCartItemDeleteForm",
 *     },
 *     "access" = "Drupal\webform_cart\WebformCartItemAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\webform_cart\WebformCartItemHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "webform_cart_item",
 *   admin_permission = "administer webform cart item entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/webformcart/webform_cart_item/{webform_cart_item}",
 *     "add-page" = "/admin/structure/webformcart/webform_cart_item/add",
 *     "add-form" = "/admin/structure/webformcart/webform_cart_item/add/{webform_cart_item_type}",
 *     "edit-form" = "/admin/structure/webformcart/webform_cart_item/{webform_cart_item}/edit",
 *     "delete-form" = "/admin/structure/webformcart/webform_cart_item/{webform_cart_item}/delete",
 *     "collection" = "/admin/structure/webformcart/webform_cart_item",
 *   },
 *   bundle_entity_type = "webform_cart_item_type",
 *   field_ui_base_route = "entity.webform_cart_item_type.edit_form"
 * )
 */
class WebformCartItem extends ContentEntityBase implements WebformCartItemInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
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
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('name'))
      ->setDescription(t('The name of the Webform cart item entity entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Order ID'))
      ->setDescription(t('Parent Order.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'webform_cart_order')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['original_product'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Product'))
      ->setDescription(t('Original Product Nodd ID'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The number of purchased units.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setSetting('min', 0)
      ->setDefaultValue(1)
      ->setDisplayOptions('form', [
        'type' => 'commerce_quantity',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantitySetting'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Quantity Settings'))
      ->setDescription(t('Storage for quantity setting config.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 1000)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data1'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Data 1'))
      ->setDescription(t('Storage field for additional data.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 1000)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['data2'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Data 2'))
      ->setDescription(t('Storage field for additional data.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 1000)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
