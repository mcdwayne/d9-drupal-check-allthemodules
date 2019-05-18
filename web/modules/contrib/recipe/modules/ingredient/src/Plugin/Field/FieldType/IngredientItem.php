<?php

namespace Drupal\ingredient\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\ingredient\IngredientUnitTrait;

/**
 * Plugin implementation of the 'ingredient' field type.
 *
 * @FieldType(
 *   id = "ingredient",
 *   label = @Translation("Ingredient"),
 *   description = @Translation("This field stores the ID of an ingredient as an integer value."),
 *   category = @Translation("Reference"),
 *   default_widget = "ingredient_autocomplete",
 *   default_formatter = "ingredient_default",
 *   list_class = "\Drupal\ingredient\Plugin\Field\FieldType\IngredientFieldItemList",
 * )
 */
class IngredientItem extends EntityReferenceItem {

  use IngredientUnitTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'ingredient',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'default_unit' => '',
      'unit_sets' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_id' => [
          'description' => 'The ID of the ingredient entity.',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
        'quantity' => [
          'type' => 'float',
          'not null' => FALSE,
        ],
        'unit_key' => [
          'description' => 'Untranslated unit key from the units array.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'note' => [
          'description' => 'Ingredient processing or notes related to recipe.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
      'foreign keys' => [
        'target_id' => [
          'table' => 'ingredient',
          'columns' => ['target_id' => 'id'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['quantity'] = DataDefinition::create('')
      ->setLabel(t('Quantity'));

    $properties['unit_key'] = DataDefinition::create('string')
      ->setLabel(t('Unit key'));

    $properties['note'] = DataDefinition::create('string')
      ->setLabel(t('Note'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   *
   * @todo Migrate the default_unit setting to the defaultValuesForm().
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['unit_sets'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable sets of units'),
      '#default_value' => $this->getSetting('unit_sets'),
      '#options' => $this->getUnitSetOptions(),
      '#description' => $this->t('Units in enabled sets will appear in the field widget.  If no sets are selected then all units will appear by default.'),
      '#ajax' => [
        'callback' => [$this, 'setChangeAjaxCallback'],
        'wrapper' => 'default-unit-wrapper',
      ],
    ];
    $element['default_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Default unit type for ingredients'),
      '#default_value' => $this->getSetting('default_unit'),
      '#options' => [],
      '#process' => [[$this, 'processDefaultUnit']],
      '#prefix' => '<div id="default-unit-wrapper">',
      '#suffix' => '</div>',
    ];

    return $element;
  }

  /**
   * Sets the options of the default_unit form element.
   */
  public function processDefaultUnit($element, FormStateInterface $form_state, $form) {
    $unit_sets = $form_state->getValue(['settings', 'unit_sets']);

    $units = $this->getConfiguredUnits($unit_sets);
    $units = $this->sortUnitsByName($units);
    $element['#options'] = $this->createUnitSelectOptions($units);

    // If the #default_value is not in the current list of units due to an AJAX
    // reload, unset it to prevent a validation error when reloading.
    if (!isset($element['#options'][$element['#default_value']])) {
      unset($element['#default_value']);
      unset($element['#value']);
    }
    return $element;
  }

  /**
   * Ajax callback for the unit_sets form element.
   */
  public function setChangeAjaxCallback(array $form, FormStateInterface $form_state) {
    return $form['settings']['default_unit'];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();

    // Get the ingredient unit keys.
    $unit_keys = array_keys($this->getConfiguredUnits());
    $random_unit_key = mt_rand(0, count($unit_keys) - 1);

    // Generate an ingredient entity.
    $ingredient = \Drupal::entityTypeManager()->getStorage('ingredient')->create(['name' => $random->name(10, TRUE)]);
    $values = [
      'target_id' => $ingredient->id(),
      'quantity' => mt_rand(1, 5),
      'unit_key' => $unit_keys[$random_unit_key],
      'note' => $random->word(15),
    ];
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    return [];
  }

}
