<?php

namespace Drupal\header_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'header_field_type' field type.
 *
 * @FieldType(
 *   id = "header_field_type",
 *   label = @Translation("Header Title"),
 *   description = @Translation("Header that allows you to enter header title with few attributes."),
 *   default_widget = "header_field_widget_type",
 *   default_formatter = "header_formatter_type"
 * )
 */
class HeaderFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array(
        'max_length' => 255,
    ) + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Text value'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['weight'] = DataDefinition::create('string')
        ->setLabel(t('Weight'))
        ->setRequired(TRUE);

    $properties['alignment'] = DataDefinition::create('string')
        ->setLabel(t('Alignment'))
        ->setRequired(TRUE);   
    
    $properties['h_tag'] = DataDefinition::create('string')
        ->setLabel(t('H Tag'))
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
            'weight' => [
                'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
                'length' => (int) $field_definition->getSetting('max_length'),
                'binary' => $field_definition->getSetting('case_sensitive'),
            ],
            'alignment' => [
                'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
                'length' => (int) $field_definition->getSetting('max_length'),
                'binary' => $field_definition->getSetting('case_sensitive'),

            ],    
            'h_tag' => [
                'type' => $field_definition->getSetting('is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
                'length' => (int) $field_definition->getSetting('max_length'),
                'binary' => $field_definition->getSetting('case_sensitive'),
            ],
        ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    if ($max_length = $this->getSetting('max_length')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', array(
          'value' => array(
              'Length' => array(
                  'max' => $max_length,
                  'maxMessage' => t('%name: may not be longer than @max characters.', array('%name' => $this->getFieldDefinition()->getLabel(), '@max' => $max_length)),
              ),
          ),
      ));
    }

    return $constraints;
  }


  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value1 = $this->get('value')->getValue();
    $value2 = $this->get('weight')->getValue();
    $value3 = $this->get('alignment')->getValue();
    $value4 = $this->get('h_tag')->getValue();
    return empty($value1) && empty($value2) && empty($value3) && empty($value4);
   }
  
  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = array();

    $element['max_length'] = array(
        '#type' => 'number',
        '#title' => t('Maximum length'),
        '#default_value' => $this->getSetting('max_length'),
        '#required' => TRUE,
        '#description' => t('The maximum length of the field in characters.'),
        '#min' => 1,
        '#disabled' => $has_data,
    );

    return $element;
  }

}
