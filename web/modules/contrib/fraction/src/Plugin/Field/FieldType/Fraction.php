<?php

namespace Drupal\fraction\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'fraction' field type.
 *
 * @FieldType(
 *   id = "fraction",
 *   label = @Translation("Fraction (two integers)"),
 *   description = @Translation("This field stores a decimal in fraction form (with a numerator and denominator) for maximum precision."),
 *   category = @Translation("Number"),
 *   default_widget = "fraction",
 *   default_formatter = "fraction"
 * )
 */
class Fraction extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'numerator' => array(
          'description' => 'Fraction numerator value',
          'type' => 'int',
          'size' => 'big',
          'not null' => TRUE,
          'default' => 0,
        ),
        'denominator' => array(
          'description' => 'Fraction denominator value',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 1,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $property_definitions['numerator'] = DataDefinition::create('integer')
      ->setLabel(t('Numerator value'));
    $property_definitions['denominator'] = DataDefinition::create('integer')
      ->setLabel(t('Denominator value'));
    $property_definitions['fraction'] = MapDataDefinition::create()
      ->setLabel(t('Fraction'))
      ->setDescription(t('A fraction object instance.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\fraction\FractionProperty');
    return $property_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $numerator = $this->get('numerator')->getValue();
    $denominator = $this->get('denominator')->getValue();
    return ((string) $numerator !== '0' && empty($numerator)) || empty($denominator);
  }
}
