<?php

/**
 * @file
 * Contains \Drupal\float_big\Plugin\Field\FieldType\FloatBigType.
 */

namespace Drupal\float_big\Plugin\Field\FieldType;


use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

use Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase;

/**
 * Defines the 'float_big' field type.
 *
 * @FieldType(
 *   id = "float_big",
 *   label = @Translation("Number (float_big)"),
 *   description = @Translation("This field stores a number in the database in a floating_big point format."),
 *   category = @Translation("Number"),
 *   default_widget = "number",
 *   default_formatter = "number_decimal"
 * )
 */
class FloatBigType extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('float')
        ->setLabel(new TranslatableMarkup('float_big'))
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
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $element['min']['#step'] = 'any';
    $element['max']['#step'] = 'any';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $precision = rand(10, 32);
    $scale = rand(0, 2);
    $max = is_numeric($settings['max']) ?: pow(10, ($precision - $scale)) - 1;
    $min = is_numeric($settings['min']) ?: -pow(10, ($precision - $scale)) + 1;
    // @see "Example #1 Calculate a random floating_big point number" in
    // http://php.net/manual/en/function.mt-getrandmax.php
    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    $values['value'] = self::truncateDecimal($random_decimal, $scale);
    return $values;
  }

}

  
  
  
  
  
  