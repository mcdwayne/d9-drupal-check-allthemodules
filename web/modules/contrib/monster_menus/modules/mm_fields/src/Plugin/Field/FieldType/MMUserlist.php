<?php

/*
 * mm_userlist field type.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *  id = "mm_userlist",
 *  label = @Translation("MM User List"),
 *  description = @Translation("Contains a list of users. The data is stored in an integer field."),
 *  default_widget = "mm_userlist",
 *  default_formatter = "mm_fields_user",
 * )
 */
class MMUserlist extends FieldItemBase {

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
      ->setLabel(t('Users'))
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
    $values['value'] = [0 => mm_content_uid2name(0)];
    return $values;
  }

}