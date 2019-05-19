<?php

namespace Drupal\webform_cart\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Webform cart order entity entity.
 *
 * @ingroup webform_cart
 *
 * @ContentEntityType(
 *   id = "webform_cart_order",
 *   label = @Translation("Webform cart order entity"),
 *   bundle_label = @Translation("Webform cart order entity type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\webform_cart\WebformCartOrderListBuilder",
 *     "views_data" = "Drupal\webform_cart\Entity\WebformCartOrderViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\webform_cart\Form\WebformCartOrderForm",
 *       "add" = "Drupal\webform_cart\Form\WebformCartOrderForm",
 *       "edit" = "Drupal\webform_cart\Form\WebformCartOrderForm",
 *       "delete" = "Drupal\webform_cart\Form\WebformCartOrderDeleteForm",
 *     },
 *     "access" = "Drupal\webform_cart\WebformCartOrderAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\webform_cart\WebformCartOrderHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "webform_cart_order",
 *   admin_permission = "administer webform cart order entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/webformcart/webform_cart_order/{webform_cart_order}",
 *     "add-page" = "/admin/structure/webformcart/webform_cart_order/add",
 *     "add-form" = "/admin/structure/webformcart/webform_cart_order/add/{webform_cart_order_type}",
 *     "edit-form" = "/admin/structure/webformcart/webform_cart_order/{webform_cart_order}/edit",
 *     "delete-form" = "/admin/structure/webformcart/webform_cart_order/{webform_cart_order}/delete",
 *     "collection" = "/admin/structure/webformcart/webform_cart_order",
 *   },
 *   bundle_entity_type = "webform_cart_order_type",
 *   field_ui_base_route = "entity.webform_cart_order_type.edit_form"
 * )
 */
class WebformCartOrder extends ContentEntityBase implements WebformCartOrderInterface {

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

    $fields['order_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order number'))
      ->setDescription(t('The order number displayed to the customer.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Webform cart order entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['placed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Placed'))
      ->setDescription(t('The time when the order was placed.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time when the order was completed.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP address'))
      ->setDescription(t('The IP address of the order.'))
      ->setDefaultValue('')
      ->setSetting('max_length', 128)
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

    $fields['order_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order Status'))
      ->setDescription(t('Status of the order, Started, Processing, Complete.'))
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
