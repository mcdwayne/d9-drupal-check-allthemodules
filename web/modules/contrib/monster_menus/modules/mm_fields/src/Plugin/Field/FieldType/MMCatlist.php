<?php

/*
 * mm_catlist field type.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *  id = "mm_catlist",
 *  label = @Translation("MM Page List"),
 *  description = @Translation("Contains a list of MM pages. The data is stored in an integer field."),
 *  default_widget = "mm_catlist",
 *  default_formatter = "mm_fields_link_page",
 * )
 */
class MMCatlist extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('MM Pages'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->get('value')->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = [mm_home_mmtid() => mm_content_get_name(mm_home_mmtid())];
    return $values;
  }

}