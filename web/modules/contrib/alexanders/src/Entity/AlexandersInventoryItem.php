<?php

namespace Drupal\alexanders\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Alexanders Inventory Item.
 *
 * These are items Alexanders fulfills but is not required to be printed.
 *
 * @ContentEntityType(
 *   id = "alexanders_inventory_item",
 *   label = @Translation("Alexanders Inventory Item"),
 *   label_singular = @Translation("Alexanders Inventory Item"),
 *   label_plural = @Translation("Alexanders Inventory Items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count item",
 *     plural = "@count items",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *   },
 *   base_table = "alexanders_inventory_item",
 *   data_table = "alexanders_inventory_item_data",
 *   admin_permission = "administer site settings",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "item_id",
 *     "label" = "sku",
 *   },
 * )
 */
class AlexandersInventoryItem extends ContentEntityBase implements AlexandersInventoryItemInterface {

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
  public function getSku() {
    return $this->get('sku')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSku($sku) {
    $this->set('sku', $sku);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuantity() {
    return $this->get('quantity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuantity($quantity) {
    $this->set('quantity', $quantity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item description'))
      ->setRequired(FALSE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item SKU'))
      ->setRequired(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Quantity'))
      ->setDescription(t('Quantity of items.'))
      ->setRequired(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
