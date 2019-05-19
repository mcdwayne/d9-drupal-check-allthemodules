<?php

namespace Drupal\rut_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\rut\Rut;

/**
 * Plugin implementation of the 'rut_field_rut' field type.
 *
 * @FieldType(
 *   id = "rut_field_rut",
 *   label = @Translation("Rut Field"),
 *   module = "rut_field",
 *   description = @Translation("Field to store RUN or RUT."),
 *   default_widget = "rut_field_widget",
 *   constraints = {"RutFieldType" = {}},
 *   default_formatter = "rut_field_formatter_default"
 * )
 */
class RutItem extends FieldItemBase {
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'rut' => [
          'type' => 'int',
          'not null' => FALSE,
          'unsigned' => TRUE,
          'size' => 'big',
        ],
        'dv' => [
          'type' => 'char',
          'not null' => FALSE,
          'length' => 1,
        ],
      ],
      'indexes' => [
        'rut' => ['rut'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('rut')->getValue();

    return empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    if (is_string($value) || is_numeric($value)) {
      $this->set('value', $value, $notify);
    }
    elseif (isset($value['value']) && !is_array($value['value'])) {
      $this->set('value', $value['value'], $notify);
    }
    elseif (is_array($value) && !isset($value['value']) && isset($value['rut']) && isset($value['dv'])) {
      $value['value'] = Rut::formatterRut($value['rut'], $value['dv']);
      parent::setValue($value, FALSE);
    }
    else {
      parent::setValue($value, FALSE);
    }

    // Notify the parent if necessary.
    if ($notify && $this->parent) {
      $this->parent->onChange($this->getName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    if ($property_name == 'value') {
      list($rut, $dv) = Rut::separateRut($this->get('value')->getString());
      $this->writePropertyValue('rut', $rut);
      $this->writePropertyValue('dv', $dv);
    }
    elseif ($property_name == 'rut' || $property_name == 'dv') {
      $rut_complete = Rut::formatterRut($this->get('rut')->getString(), $this->get('dv')->getString());
      $this->writePropertyValue('value', $rut_complete);
    }

    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $values = parent::getValue();
    $values['value'] = $this->get('value')->getString();

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['rut'] = DataDefinition::create('integer')
      ->setLabel(t('Rut value'));
    $properties['dv'] = DataDefinition::create('string')
      ->setLabel(t('Dv value'));
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Rut Complete value'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    list($rut, $dv) = Rut::generateRut(FALSE);

    $values = [
      'rut' => $rut,
      'dv' => $dv,
    ];

    return $values;
  }
}
