<?php

namespace Drupal\commerce_inventory\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the Inventory Quantity field type.
 *
 * @FieldType(
 *   id = "entity_reference_inventory_quantity",
 *   label = @Translation("Entity reference w/quantity"),
 *   description = @Translation("Entity reference with associated quantity"),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_inventory_quantity_autocomplete",
 *   default_formatter = "entity_reference_inventory_quantity_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList"
 * )
 */
class EntityReferenceInventoryQuantity extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $quantity_definition = DataDefinition::create('float')
      ->setLabel($field_definition->getSetting('quantity_label'));
    $properties['quantity'] = $quantity_definition;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['quantity'] = [
      'type' => 'float',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'quantity_label' => t('Quantity'),
      'min' => '',
      'max' => '',
      'prefix' => '',
      'suffix' => '',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);

    $form['quantity'] = [
      '#type' => 'details',
      '#title' => t('Quantity'),
      '#open' => TRUE,
    ];

    $form['quantity']['min'] = [
      '#type' => 'number',
      '#title' => t('Minimum'),
      '#default_value' => $this->getSetting('min'),
      '#step' => 'any',
    ];
    $elements['quantity']['max'] = [
      '#type' => 'number',
      '#title' => t('Maximum'),
      '#default_value' => $this->getSetting('max'),
      '#step' => 'any',
    ];
    $elements['quantity']['quantity_label'] = [
      '#type' => 'textfield',
      '#title' => t('Quantity Label'),
      '#default_value' => $this->getSetting('quantity_label'),
      '#description' => t('Also used as a placeholder in multi-value instances.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    $settings = $this->getSettings();
    $label = $this->getFieldDefinition()->getLabel();

    if (!empty($settings['min'])) {
      $min = $settings['min'];
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'min' => $min,
            'minMessage' => t('%name: the value may be no less than %min.', ['%name' => $label, '%min' => $min]),
          ],
        ],
      ]);
    }

    if (!empty($settings['max'])) {
      $max = $settings['max'];
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'max' => $max,
            'maxMessage' => t('%name: the value may be no greater than %max.', ['%name' => $label, '%max' => $max]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // First check if entity reference is set.
    if (parent::isEmpty()) {
      return TRUE;
    }

    // Then check if quantity is set..
    if (empty($this->quantity) && (string) $this->quantity !== '0') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);

    $settings = $field_definition->getSettings();
    $precision = rand(10, 32);
    $scale = rand(0, 2);
    $max = is_numeric($settings['max']) ?: pow(10, ($precision - $scale)) - 1;
    $min = is_numeric($settings['min']) ?: -pow(10, ($precision - $scale)) + 1;

    $random_decimal = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    $values['quantity'] = self::truncateDecimal($random_decimal, $scale);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }

  /**
   * Helper method to truncate a decimal number to a given number of decimals.
   *
   * @param float $decimal
   *   Decimal number to truncate.
   * @param int $num
   *   Number of digits the output will have.
   *
   * @return float
   *   Decimal number truncated.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase
   */
  protected static function truncateDecimal($decimal, $num) {
    return floor($decimal * pow(10, $num)) / pow(10, $num);
  }

}
