<?php

namespace Drupal\number_double\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase;

/**
 * Defines the 'double' field type.
 *
 * @FieldType(
 *   id = "double",
 *   label = @Translation("Number (double)"),
 *   description = @Translation("This field stores a number in the database as a DOUBLE type."),
 *   category = @Translation("Number"),
 *   default_widget = "number_double",
 *   default_formatter = "number_double"
 * )
 */
class DoubleItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
      ->setLabel(t('Double'))
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
          'type' => 'float',
          'size' => 'big',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'min' => '',
      'max' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    $settings = $this->getSettings();

    $element['min'] = array(
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $settings['min'],
      '#description' => t('The minimum value that should be allowed in this field. Leave blank for no minimum.'),
      '#step' => 'any',
    );

    $element['max'] = array(
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $settings['max'],
      '#description' => t('The maximum value that should be allowed in this field. Leave blank for no maximum.'),
      '#step' => 'any',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $label = $this->getFieldDefinition()->getLabel();

    // DOUBLE values have a maximum length of 53.
    $max_length = 53;
    $constraints[] = $constraint_manager->create('ComplexData', array(
      'value' => array(
        'Length' => array(
          'max' => $max_length,
          'maxMessage' => t('%name: the value may be no longer than %max digits.', array('%name' => $label, '%max' => $max_length)),
        ),
      ),
    ));

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $precision = rand(10, 53);
    $scale = rand(0, 10);
    $max = is_numeric($settings['max']) ?: pow(10, ($precision - $scale)) - 1;
    $min = is_numeric($settings['min']) ?: -pow(10, ($precision - $scale)) + 1;
    // @see "Example #1 Calculate a random floating-point number" in
    // http://php.net/manual/en/function.mt-getrandmax.php
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    $values['value'] = self::truncateDecimal($random_decimal, $scale);
    return $values;
  }

}
