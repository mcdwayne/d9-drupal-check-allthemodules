<?php

namespace Drupal\coordinate_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'coordinate_field' field type.
 *
 * @FieldType(
 *   id = "coordinate_field",
 *   label = @Translation("Coordinate"),
 *   description = @Translation("Store X and Y coordinate values"),
 *   default_widget = "coordinate_default",
 *   default_formatter = "coordinate_default"
 * )
 */
class CoordinateField extends FieldItemBase {


  public static function defaultFieldSettings() {

    return array(
      'xpos' => t('Position X'),
      'ypos' => t('Position Y')
    ) + parent::defaultFieldSettings();

  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();

    $element['xpos'] = array(
      '#default_value' => $this->getSetting('xpos'),
      '#size' => 20,
      '#title' => t('X value label'),
      '#type' => 'textfield',
    );

    $element['ypos'] = array(
      '#default_value' => $this->getSetting('ypos'),
      '#size' => 20,
      '#title' => t('Y value label'),
      '#type' => 'textfield',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['xpos'] = DataDefinition::create('float')
      ->setLabel(t('X Position'));

    $properties['ypos'] = DataDefinition::create('float')
      ->setLabel(t('Y Position'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'xpos' => [
          'description' => 'X Position',
          'type' => 'float',
          'size' => 'big',
          'not null' => FALSE,
          'default' => 0,
        ],
        'ypos' => [
          'description' => 'Y Position',
          'type' => 'float',
          'size' => 'big',
          'not null' => FALSE,
          'default' => 0,
        ],
      ],
      'indexes' => [
        'xpos' => ['xpos'],
        'ypos' => ['ypos'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    return $constraints;
  }

}
