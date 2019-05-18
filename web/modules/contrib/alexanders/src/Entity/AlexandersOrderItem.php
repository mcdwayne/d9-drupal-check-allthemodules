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
 *   id = "alexanders_order_item",
 *   label = @Translation("Alexanders Order Item"),
 *   label_singular = @Translation("Alexanders Order Item"),
 *   label_plural = @Translation("Alexanders Order Items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count item",
 *     plural = "@count items",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *   },
 *   list_class = "\Drupal\alexanders\Plugin\Field\FieldType\AlexandersOrderItemList",
 *   base_table = "alexanders_order_item",
 *   data_table = "alexanders_order_item_data",
 *   admin_permission = "administer site settings",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "item_id",
 *     "label" = "sku",
 *   },
 * )
 */
class AlexandersOrderItem extends ContentEntityBase implements AlexandersOrderItemInterface {

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
  public function setQuantity($qty) {
    $this->set('quantity', $qty);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return $this->get('file')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFile($url) {
    $this->set('file', $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddFile() {
    return $this->get('foil')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddFile($url) {
    $this->set('foil', $url);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->get('width')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidth($width) {
    $this->set('width', $width);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->get('width')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeight($height) {
    $this->set('height', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFolds() {
    return $this->get('folds')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFolds($folds) {
    $this->set('folds', $folds);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isVariable() {
    return $this->get('variable')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVariable($variable) {
    $this->set('variable', $variable);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDuplex() {
    return $this->get('duplex')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDuplex($duplex) {
    $this->set('duplex', $duplex);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMedia() {
    return $this->get('media')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMedia($media) {
    $this->set('media', $media);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Item SKU'))
      ->setRequired(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
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

    $fields['file'] = BaseFieldDefinition::create('string')
      ->setLabel(t('File URL'))
      ->setDescription(t('URL of file for Alexanders to print'))
      ->setRequired(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['foil'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Foil URL'))
      ->setDescription(t('URL of foil for Alexanders to print'))
      ->setRequired(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['width'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Width'))
      ->setDescription(t('Width of product (in inches).'))
      ->setRequired(FALSE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['height'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Height'))
      ->setDescription(t('Height of product (in inches).'))
      ->setRequired(FALSE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['media'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Media'))
      ->setDescription(t('Type of printing to be done.'))
      ->setRequired(FALSE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['folds'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Folds'))
      ->setDescription(t('Specific folds.'))
      ->setRequired(FALSE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['variable'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Variable'))
      ->setDescription(t('Whether the specific item is variable.'))
      ->setRequired(FALSE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duplex'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Duplex'))
      ->setDescription(t('Whether the item should be printed double or single sided.'))
      ->setRequired(TRUE)
      ->setSetting('display_description', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
