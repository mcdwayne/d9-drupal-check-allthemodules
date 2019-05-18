<?php

namespace Drupal\color_widget\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Plugin implementation of the 'color' field type.
 *
 * @FieldType(
 *   id = "color_item",
 *   label = @Translation("Color"),
 *   description = @Translation("Get all the colors"),
 *   category = @Translation("Custom"),
 *   default_widget = "color_default",
 *   default_formatter = "color_default"
 * )
 */
class ColorItem extends FieldItemBase {

  /**
   * The colorhelper.
   *
   * @var \Drupal\color_widget\Services\ColorHelper
   */
  protected $colorHelper;

  /**
   * ColorItem constructor.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The definition.
   * @param string|null $name
   *   The name.
   * @param \Drupal\Core\TypedData\TypedDataInterface|null $parent
   *   The parent.
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct(
      $definition,
      $name,
      $parent
    );

    // Temporary solution because container injection is not working at the moment on FieldType.
    $this->colorHelper = \Drupal::service('color_widget.color_helper');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['colors' => ''] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $form = [
      '#type' => 'container',
      '#element_validate' => [[$this, 'fieldSettingsFormValidate']],
    ];

    $form['colors'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Colors'),
      '#default_value' => $this->getSetting('colors'),
      '#description' => $this->t('A list separated by pipe of colors, in hex format'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $colors = $this->colorHelper->convertTextareaToArray($form_state->getValue('settings')['colors']);
    $pattern = '/(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)|(^#[0-9A-F]{8}$)/i';
    $invalidColors = [];
    foreach ($colors as $color) {
      if (preg_match($pattern, $color) === 0) {
        $invalidColors[] = $color;
      }
    }

    if (count($invalidColors) > 0) {
      $form_state->setErrorByName(
        'colors',
        t('The color(s) "@colors" are not valid color(s)', ['@colors' => implode(', ', $invalidColors)])
      );
    }
  }

  /**
   * Static schema.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   The field defintion.
   *
   * @return array
   *   The return array.
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'char',
          'not null' => FALSE,
          'length' => 255,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * Static propertyDefinitions.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return mixed
   *   The return of the propertydefinition.
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')->setLabel(t('Color'));
    return $properties;
  }
}