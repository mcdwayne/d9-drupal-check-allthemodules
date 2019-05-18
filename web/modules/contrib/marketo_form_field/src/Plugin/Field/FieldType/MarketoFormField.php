<?php

namespace Drupal\marketo_form_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'marketo_form_field' field type.
 *
 * @FieldType(
 *   id = "marketo_form_field",
 *   label = @Translation("Marketo Form Field"),
 *   description = @Translation("This field the Marketo form field ID."),
 *   category = @Translation("Marketo"),
 *   default_widget = "string_textfield",
 *   default_formatter = "marketo_form"
 * )
 */
class MarketoFormField extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['success_message'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Success Message'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
        'success_message' => [
          'type' => 'varchar',
          'length' => $field_definition->getSetting('max_length'),
          'binary' => $field_definition->getSetting('case_sensitive'),
        ],
      ],
    ];
  }

}
