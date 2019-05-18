<?php

/*
 * mm_nodelist field type.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *  id = "mm_nodelist",
 *  label = @Translation("MM Node List"),
 *  description = @Translation("Contains a list of nodes on MM pages. The data is stored in a pair of integer fields."),
 *  default_widget = "mm_nodelist",
 *  default_formatter = "mm_fields_link_node_title",
 * )
 */
class MMNodelist extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'mmtid' => [
          'type' => 'int',
        ],
        'nid' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['mmtid'] = DataDefinition::create('integer')
      ->setLabel(t('MM Tree ID'))
      ->setRequired(TRUE);
    $properties['nid'] = DataDefinition::create('integer')
      ->setLabel(t('Node ID'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('mmtid')->getValue()) && empty($this->get('nid')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['mmtid'] = $values['nid'] = [];
    return $values;
  }

}