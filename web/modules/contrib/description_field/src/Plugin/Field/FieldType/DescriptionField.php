<?php

namespace Drupal\description_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'description' field type.
 *
 * @FieldType(
 *   id = "description_field",
 *   label = @Translation("Description"),
 *   description = @Translation("A field type used for displaying a description."),
 *   default_widget = "description_field_standard",
 *   default_formatter = "description_field_default"
 * )
 */
class DescriptionField extends FieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  protected static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'long_description' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'long_description' => [
          'type' => 'varchar',
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['long_description'] = DataDefinition::create('string')
      ->setLabel(t('Long description'))
      ->setDescription(t('The text from the field settings.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $long_description_setting = $this->getSetting('long_description');

    $element['long_description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#format' => $long_description_setting['format'] ?? filter_default_format(),
      '#default_value' => $long_description_setting['value'] ?? '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return TRUE;
  }

}
