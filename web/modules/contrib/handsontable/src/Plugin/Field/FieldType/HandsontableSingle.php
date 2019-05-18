<?php

/**
 * @file
 * Contains \Drupal\handsontable\Plugin\Field\FieldType\HandsontableSingle.
 */

namespace Drupal\handsontable\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'handsontable_single' field type.
 *
 * @FieldType(
 *   id = "handsontable_single",
 *   label = @Translation("Handsontable - single"),
 *   default_widget = "handsontable_single",
 *   default_formatter = "json_single"
 * )
 */
class HandsontableSingle extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {

    $settings = [];

    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element = array();

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {

    $settings = $this->getSettings();

    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();


    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $settings = $field_definition->getSettings();

    $columns = [];

    $columns['value'] = [
      'description' => 'JSON data.',
      'type' => 'blob',
      'not null' => TRUE,
      'size' => 'big',
    ];

    return ['columns' => $columns];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
        ->setLabel(t('Text'))
        ->setRequired(TRUE);

    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public function isEmpty() {


  }



}
