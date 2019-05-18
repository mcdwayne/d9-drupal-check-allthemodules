<?php

namespace Drupal\commerce_inventory\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\core_extend\Entity\EntityCreatedTrait;
use Drupal\core_extend\Entity\EntityOwnerTrait;

/**
 * Defines the Inventory Adjustment entity.
 *
 * @ingroup commerce_inventory
 *
 * @ContentEntityType(
 *   id = "commerce_inventory_adjustment",
 *   label = @Translation("Inventory Adjustment"),
 *   label_collection = @Translation("Inventory Adjustments"),
 *   label_singular = @Translation("inventory adjustment"),
 *   label_plural = @Translation("inventory adjustments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count inventory adjustment",
 *     plural = "@count inventory adjustments"
 *   ),
 *   bundle_label = @Translation("Inventory Adjustment type"),
 *   bundle_plugin_type = "commerce_inventory_adjustment_type",
 *   handlers = {
 *     "storage" = "Drupal\commerce_inventory\Entity\Storage\InventoryAdjustmentStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_inventory\Entity\ListBuilder\InventoryAdjustmentListBuilder",
 *     "views_data" = "Drupal\commerce_inventory\Entity\ViewsData\InventoryAdjustmentViewsData",
 *     "access" = "Drupal\commerce_inventory\Entity\Access\InventoryAdjustmentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_inventory\Entity\Routing\InventoryAdjustmentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_inventory_adjustment",
 *   admin_permission = "administer inventory adjustment",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "commerce_inventory_adjustment_type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/commerce/config/inventory/adjustment",
 *   }
 * )
 */
class InventoryAdjustment extends ContentEntityBase implements InventoryAdjustmentInterface {

  use EntityCreatedTrait;
  use EntityOwnerTrait;

  /**
   * The loaded bundle plugin.
   *
   * @var \Drupal\commerce_inventory\Plugin\Commerce\InventoryAdjustmentType\InventoryAdjustmentTypeInterface
   */
  protected $bundlePlugin = NULL;

  /**
   * {@inheritdoc}
   */
  public function getData($key, $default = NULL) {
    $data = [];
    if (!$this->get('data')->isEmpty()) {
      $data = $this->get('data')->first()->getValue();
    }
    return isset($data[$key]) ? $data[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setData($key, $value) {
    $this->get('data')->__set($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription($link = FALSE) {
    return $this->getType()->getSentenceLabel($this, $link);
  }

  /**
   * {@inheritdoc}
   */
  public function setItemId($item_id) {
    if (is_int($item_id)) {
      $this->set('item_id', $item_id);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemId() {
    if (!$this->get('item_id')->isEmpty()) {
      return $this->get('item_id')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setItem(InventoryItemInterface $item) {
    $this->set('item_id', $item->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getItem() {
    if (!$this->get('item_id')->isEmpty()) {
      return $this->get('item_id')->entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    if ($item = $this->getItem()) {
      return $item->getLocation();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPurchasableEntity() {
    if ($item = $this->getItem()) {
      return $item->getPurchasableEntity();
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    return $this->set('quantity', $quantity);
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    if (!$this->get('quantity')->isEmpty()) {
      return $this->get('quantity')->value;
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRelatedAdjustment() {
    return ($this->get('related_adjustment')->isEmpty() == FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setRelatedAdjustmentId($adjustment_id) {
    if (is_int($adjustment_id)) {
      $this->set('related_adjustment', $adjustment_id);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedAdjustmentId() {
    if ($this->hasRelatedAdjustment()) {
      return $this->get('related_adjustment')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRelatedAdjustment(InventoryAdjustmentInterface $adjustment) {
    $this->set('related_adjustment', $adjustment);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedAdjustment() {
    if ($this->hasRelatedAdjustment()) {
      return $this->get('related_adjustment')->entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    if (is_null($this->bundlePlugin)) {
      $this->bundlePlugin = \Drupal::service('plugin.manager.commerce_inventory_adjustment_type')->createInstance($this->bundle());
    }
    return $this->bundlePlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getDescription();
  }

  /**
   * {@inheritdoc}
   */
  public function set($name, $value, $notify = TRUE) {
    // Ensure the quantity is always correctly adjusted based on type.
    if ($name == 'quantity') {
      $value = $this->getType()->adjustQuantity($value, $this->getItem()->getQuantity(FALSE));
    }
    return parent::set($name, $value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Re-ensure the quantity is correctly adjusted before save.
    $quantity = $this->getQuantity();
    $this->setQuantity($quantity);
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['item_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Inventory Item'))
      ->setDescription(t('The inventory item which the adjustment modified.'))
      ->setSetting('target_type', 'commerce_inventory_item')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Quantity'))
      ->setDescription(t('The quantity adjustment.'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['related_adjustment'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Adjustment'))
      ->setDescription(t('An adjustment related to this adjustment; IE moved inventory.'))
      ->setSetting('target_type', 'commerce_inventory_adjustment')
      ->setSetting('handler', 'default')
      ->setReadOnly(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Created by'))
      ->setDefaultValueCallback('Drupal\commerce_inventory\Entity\InventoryAdjustment::getCurrentUserId')
      ->setDescription(t('The user ID of the creator of the adjustment.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the adjustment was created.'));

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('A serialized array of additional data.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    // Disable plugin on sleep so it isn't serialized.
    $this->bundlePlugin = NULL;
    return parent::__sleep();
  }

}
