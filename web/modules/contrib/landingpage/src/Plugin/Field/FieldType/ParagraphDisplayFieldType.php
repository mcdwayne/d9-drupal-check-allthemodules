<?php

namespace Drupal\landingpage\Plugin\Field\FieldType;

// Use Drupal\Component\Utility\Random;.
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
// Use Drupal\Core\Form\FormStateInterface;.
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'paragraph_display_field_type' field type.
 *
 * @FieldType(
 *   id = "paragraph_display_field_type",
 *   label = @Translation("Paragraph display field type"),
 *   description = @Translation("Set individual display type for the paragraph entity."),
 *   default_widget = "paragraph_display_field_widget",
 *   default_formatter = "paragraph_display_field_formatter"
 * )
 */
class ParagraphDisplayFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  /*public static function defaultStorageSettings() {
  return array(
  'max_length' => 255,
  'is_ascii' => FALSE,
  'case_sensitive' => FALSE,
  ) + parent::defaultStorageSettings();
  }*/

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Paragraph Display'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
