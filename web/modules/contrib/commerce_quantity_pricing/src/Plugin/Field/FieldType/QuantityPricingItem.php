<?php

namespace Drupal\commerce_quantity_pricing\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Defines the 'quantity_pricing' entity field type.
 *
 * @FieldType(
 *   id = "quantity_pricing",
 *   label = @Translation("Quantity Pricing"),
 *   module = "commerce_quantity_pricing",
 *   description = @Translation("An entity field containing bulk field pricing."),
 *   default_widget = "commerce_quantity_pricing_quantity",
 * )
 */
class QuantityPricingItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['min'] = DataDefinition::create('integer')
      ->setLabel(t('Minimum value'));
    $properties['max'] = DataDefinition::create('integer')
      ->setLabel(t('Maximum value'));
    $properties['price'] = DataDefinition::create('string')
      ->setLabel(t('Price'));
    $properties['step'] = DataDefinition::create('integer')
      ->setLabel(t('Step value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'min' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'max' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'price' => [
          'type' => 'text',
        ],
        'step' => [
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
    ];
  }

  /**
   * If a max value is set to 0, remove it, otherwise set the value.
   *
   * The form that submits this is a bit weird so we need to pull the array out.
   */
  public function preSave() {
    parent::preSave();
    $values = $this->getValue()['value'];
    if ($values['max'] < 1) {
      $this->delete();
    }
    else {
      $this->setValue($values);
    }
  }

}
