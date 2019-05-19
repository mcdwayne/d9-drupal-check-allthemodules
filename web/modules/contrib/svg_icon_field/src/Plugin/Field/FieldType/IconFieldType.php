<?php

namespace Drupal\svg_icon_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'icon_field_type' field type.
 *
 * @FieldType(
 *   id = "icon_field_type",
 *   label = @Translation("SVG icon"),
 *   description = @Translation("Allows to create SVG icon field type"),
 *   category = @Translation("Reference"),
 *   default_widget = "icon_widget_type",
 *   default_formatter = "icon_formatter_type"
 * )
 */
class IconFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'source' => self::getIconsSources('default'),
      'is_ascii' => FALSE,
      'case_sensitive' => TRUE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * Returns icons sources.
   */
  public static function getIconsSources($option = NULL) {
    $sources = [
      'default' => 'Default',
    ];
    if (!empty($option)) {
      return $sources[$option];
    }
    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // !IMPORTANT.
    // By providing ->setRequired(TRUE); to each field
    // it caused field error during save. It claimed
    // the value cannot be empty, even if it didn't.
    // This needs additional investigation in order to know
    // why this happens, otherwise ->setRequired(TRUE); can be just removed.

    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['source'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Source'));

    $properties['group'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Group'));

    $properties['icon'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Icon'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'source' => [
          'description' => 'Source of icons',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'group' => [
          'description' => 'Group',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'icon' => [
          'description' => 'Icon',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element = [];

    // $element['source'] = [
    //   '#title' => $this->t('Icons source'),
    //   '#type' => 'select',
    //   '#options' => self::getIconsSources(),
    //   '#default_value' => $this->getSetting('source'),
    //   '#weight' => -9999,
    // ];

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * This form is available when field is being created.
   * It's available on Field Settings page, right after new field name
   * is added. These settings cannot be changed if there are values
   * provided in entities for this field.
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    //$elements = parent::storageSettingsForm($form, $form_state, $has_data);

    $elements['source'] = [
      '#type' => 'select',
      '#title' => t('Icons source'),
      '#default_value' => $this->getSetting('source'),
      '#options' => self::getIconsSources(),
      '#required' => FALSE,
      '#description' => $this->t('Source of icons.'),
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('icon')->getValue();
    return $value === NULL || $value === '';
  }

}
