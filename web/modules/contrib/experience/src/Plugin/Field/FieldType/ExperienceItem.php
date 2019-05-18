<?php

namespace Drupal\experience\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'experience' field type.
 *
 * @FieldType(
 *   id = "experience",
 *   label = @Translation("Experience"),
 *   description = @Translation("This field stores a experience in the database."),
 *   category = @Translation("Number"),
 *   default_widget = "experience_default",
 *   default_formatter = "experience_default",
 *   list_class = "\Drupal\experience\Plugin\Field\FieldType\ExperienceFieldItemList"
 * )
 */
class ExperienceItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'label_position' => 'above',
      'include_fresher' => 0,
      'year_start' => 0,
      'year_end' => 30,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
          'not null' => FALSE,
          'size' => 'normal',
          'unsigned' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Experience value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $options = ['above' => t('Above'), 'within' => t('Within')];
    $description = t("The location of experience part labels, like 'Year', 'Month'. 'Above' displays the label as titles above each experience part. 'Within' inserts the label as the first option in the select list.");

    $element['label_position'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#default_value' => $this->getSetting('label_position'),
      '#title' => t('Position of experience part labels'),
      '#description' => $description,
    ];

    $element['include_fresher'] = [
      '#type' => 'checkbox',
      '#title' => t('Include fresher option'),
      '#default_value' => $this->getSetting('include_fresher'),
    ];

    $element['year_start'] = [
      '#type' => 'select',
      '#title' => t('Starting year'),
      '#default_value' => $this->getSetting('year_start'),
      '#options' => range(0, 99),
    ];

    $element['year_end'] = [
      '#type' => 'select',
      '#title' => t('Ending year'),
      '#default_value' => $this->getSetting('year_end'),
      '#options' => range(0, 99),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
