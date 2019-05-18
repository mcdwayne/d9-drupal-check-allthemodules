<?php
/**
 * Author: Remigiusz Kornaga <remkor@o2.pl>
 */

namespace Drupal\regon\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'regon' field type.
 *
 * @FieldType(
 *   id = "regon",
 *   label = @Translation("REGON"),
 *   module = "regon",
 *   description = @Translation("Contains REGON identification number."),
 *   default_widget = "regon",
 *   default_formatter = "regon"
 * )
 */
class RegonItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'number' => array(
          'type' => 'char',
          'length' => 14,
          'not null' => FALSE,
        ),
      ),
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('number')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['number'] = DataDefinition::create('string')->setLabel(t('REGON'));
    return $properties;
  }

}
