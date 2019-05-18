<?php

namespace Drupal\barcode\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

abstract class BarcodeBase extends FieldItemBase {

  /**
   * @var array $types
   *   An array of barcode types this widget supports.
   */
  public static $types = [];

  /**
   * Get the standard allowed type of barcodes.
   *
   * @return array
   *   An array of barcode types.
   */
  abstract public static function standardBarcodes();

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = ['types' => static::standardBarcodes()];
    return $settings + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $types = $this->getSetting('types');
    $element['types'] = [
      '#type' => 'textarea',
      '#title' => t('Allowed Barcode Types'),
      '#default_value' => $this->getTypesString($types),
      '#rows' => 10,
      '#access' => empty($allowed_values_function),
      '#element_validate' => [[get_class($this), 'validateTypes']],
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#current_types' => $types,
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

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Barcode'))
      ->setRequired(TRUE);
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Barcode Type'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    /*$constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();

    // @DCG Suppose our value must not be longer than 10 characters.
    $options['value']['Length']['max' = 10;

    // @DCG
    // See /core/lib/Drupal/Core/Validation/Plugin/Validation/Constraint
    // directory for available constraints.
    $constraints[] = $constraint_manager->create('ComplexData', $options);
    */
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $columns = [
      'value' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Barcode Data.',
        'length' => 255,
      ],
      'type' => [
        'type' => 'varchar',
        'not null' => FALSE,
        'description' => 'Barcode Type.',
        'length' => 64,
      ],
    ];

    $schema = [
      'columns' => $columns,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->word(mt_rand(1, 50));
    return $values;
  }

  /**
   * Generates a string representation of an array of allowed types.
   *
   * This string format is suitable for editing in a textarea.
   *
   * @param array $values
   *   An array of barcode types, where keys are types and values are labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Types are separated by a carriage return.
   *    - Each type is in the format "type|label".
   */
  protected function getTypesString($types) {
    foreach ($types as $type => $label) {
      $lines[] = "$type|$label";
    }
    return implode("\n", $lines);
  }

    /**
   * #element_validate callback for options field allowed values.
   *
   * @param $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param $form_state
   *   The current state of the form for the form this element belongs to.
   *
   * @see \Drupal\Core\Render\Element\FormElement::processPattern()
   */
  public static function validateTypes($element, FormStateInterface $form_state) {
    $types = static::extractTypes($element['#value']);

    if (!is_array($types)) {
      $form_state->setError($element, t('Allowed types list: invalid input.'));
    }
    else {
      // Check that keys are valid for the field type.
      foreach ($types as $type => $label) {
        if ($error = static::validateType($type)) {
          $form_state->setError($element, $error);
          break;
        }
      }

      // Prevent removing types currently in use.
      if ($element['#field_has_data']) {
        $lost_keys = array_keys(array_diff_key($element['#current_types'], $types));
        if (_options_values_in_use($element['#entity_type'], $element['#field_name'], $lost_keys)) {
          $form_state->setError($element, t('Allowed types list: some types are being removed while currently in use.'));
        }
      }

      $form_state->setValueForElement($element, $types);
    }
  }

  /**
   * Extracts the allowed types array from the types element.
   *
   * @param string $string
   *   The raw string to extract types/labels from.
   *
   * @return array|null
   *   The array of extracted type/label pairs, or NULL if the string is invalid.
   *
   * @see \Drupal\barcode\Plugin\Field\FieldType\BarcodeBase::getTypesString()
   */
  protected static function extractTypes($string) {

    $list = array_map('trim',  explode("\n", $string));
    $list = array_filter($list, 'strlen');

    foreach ($list as $text) {
      $matches = [];
      if (preg_match('/(.*)\|(.*)/', $text, $matches)) {
        // Trim type and label to avoid unwanted spaces issues.
        $type = trim($matches[1]);
        $label = trim($matches[2]);
      }
      else {
        return;
      }

      $types[$type] = $label;
    }

    return $types;
  }

  /**
   * Checks whether a candidate allowed value is valid.
   *
   * @param string $type
   *   The type entered by the user.
   *
   * @return string | null
   *   The error message if the specified type is invalid, NULL otherwise.
   */
  protected static function validateType($type) {
    if (!isset(static::$types[$type])) {
      return t('Allowed Type list: type %type is invalid', ['%type' => $type]);
    }
  }

}
