<?php

namespace Drupal\alexanders\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Alexanders Order entity.
 *
 * Mainly to a) remove commerce as a dependency and b) simplify management.
 *
 * @ContentEntityType(
 *   id = "alexanders_order",
 *   label = @Translation("Alexanders Order"),
 *   label_singular = @Translation("Alexanders Order"),
 *   label_plural = @Translation("Alexanders Orders"),
 *   label_count = @PluralTranslation(
 *     singular = "@count order",
 *     plural = "@count orders",
 *   ),
 *   base_table = "alexanders_order",
 *   data_table = "alexanders_order_data",
 *   admin_permission = "administer site settings",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "order_id",
 *     "label" = "order_number",
 *   },
 *   admin_permission = "administer alexanders_order",
 *   handlers = {
 *     "event" = "Drupal\alexanders\Event\OrderEvent",
 *     "storage" = "Drupal\alexanders\OrderStorage",
 *     "access" = "Drupal\alexanders\OrderAccessControlHandler",
 *     "query_access" = "Drupal\alexanders\OrderQueryAccessHandler",
 *     "permission_provider" = "Drupal\alexanders\OrderPermissionProvider",
 *     "list_builder" = "Drupal\alexanders\OrderListBuilder",
 *     "form" = {
 *       "default" = "Drupal\alexanders\Form\AlexandersOrderForm",
 *       "add" = "Drupal\alexanders\Form\OrderForm",
 *       "edit" = "Drupal\alexanders\Form\OrderForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\alexanders\OrderRouteProvider",
 *       "delete-multiple" = "Drupal\entity\Routing\DeleteMultipleRouteProvider",
 *     },
 *   },
 *   entity_keys = {
 *     "id" = "order_id",
 *     "label" = "order_number",
 *   },
 *   links = {
 *     "canonical" = "/admin/alexanders/orders/{alexanders_order}",
 *     "edit-form" = "/admin/alexanders/orders/{alexanders_order}/edit",
 *     "delete-form" = "/admin/alexanders/orders/{alexanders_order}/delete",
 *     "delete-multiple-form" = "/admin/alexanders/orders/delete",
 *     "collection" = "/admin/alexanders/orders"
 *   },
 * )
 */
class AlexandersOrder extends ContentEntityBase implements AlexandersOrderInterface {

  /**
   * {@inheritdoc}
   */
  public function getItems() {
    return $this->get('standardPrintItems')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function exportPrintItems() {
    $items = $this->get('standardPrintItems')->referencedEntities();
    $data = [];
    /** @var \Drupal\alexanders\Entity\AlexandersOrderItem $item */
    foreach ($items as $item) {
      $data[] = [
        'itemKey' => $item->id(),
        'sku' => $item->getSku(),
        'quantity' => $item->getQuantity(),
        'fileUrl' => $item->getFile(),
        'foilUrl' => $item->getAddFile(),
        'width' => $item->getWidth(),
        'height' => $item->getHeight(),
        'media' => $item->getMedia(),
        'folds' => $item->getFolds(),
        'variable' => $item->isVariable() ?? FALSE,
        'duplex' => $item->isDuplex() ?? FALSE,
      ];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function setItems(array $items) {
    $this->set('standardPrintItems', $items);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPhotobooks() {
    return $this->get('photobookItems')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function exportPhotobooks() {
    // @TODO Draw the rest of the owl.

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setPhotobooks(array $photobooks) {
    $this->set('photobookItems', $photobooks);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getShipment() {
    return $this->get('shipping')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function setShipment($shipping) {
    $this->set('shipping', $shipping);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRush() {
    return $this->get('rush')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRush($rush) {
    $this->set('rush', $rush);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDue() {
    return $this->get('dueDate')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDue($due) {
    $this->set('dueDate', $due);
    return $this;
  }

  public function getInventoryItems() {
    return $this->get('inventoryItems')->referencedEntities();
  }

  public function exportInventoryItems() {
    $items = $this->get('inventoryItems')->referencedEntities();
    $data = [];
    /** @var \Drupal\alexanders\Entity\AlexandersInventoryItem $item */
    foreach ($items as $item) {
      $data[] = [
        // The item key is optional, so we'll randomize it.
        'itemKey' => random_int(0, 99999),
        'description' => $item->getDescription(),
        'sku' => $item->getSku(),
        'quantity' => $item->getQuantity(),
      ];
    }

    return $data;
  }

  public function setInventoryItems(array $items) {
    $this->set('inventoryItems', $items);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['order_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Order Number'))
      ->setRequired(TRUE);

    $fields['standardPrintItems'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Print Items'))
      ->setDescription(t('Order items associated with the order'))
      ->setSetting('target_type', 'alexanders_order_item')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['photobookItems'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Photobook Items'))
      ->setDescription(t('Photobook items associated with the order'))
      ->setSetting('target_type', 'alexanders_order_photobook')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['inventoryItems'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Inventory Items'))
      ->setDescription(t('Inventory items associated with the order'))
      ->setSetting('target_type', 'alexanders_inventory_item')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['shipping'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Shipping'))
      ->setDescription(t('Shipping method for the order'))
      ->setSetting('target_type', 'alexanders_shipment');

    $fields['rush'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Rush order'))
      ->setDescription(t('Whether this order should be rushed'))
      ->setDefaultValue(FALSE);

    $fields['dueDate'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Due Date'));

    return $fields;
  }

}
