<?php

namespace Drupal\nutrition_label_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'nutrition_label' field type.
 *
 * @FieldType(
 *   id = "nutrition_label",
 *   label = @Translation("Nutrition Label"),
 *   description = @Translation("Stores nutrition information for display on a label."),
 *   default_widget = "nutrition_label",
 *   default_formatter = "nutrition_label"
 * )
 */
class NutritionLabelItem extends FieldItemBase {

  /**
   * Value for the 'nutrition_label_type' setting: store serialized properties.
   */
  const NUTRITION_LABEL_TYPE_SERIALIZED = 'serialized';

  /**
   * Value for the 'nutrition_label_type' setting: store individual fields.
   */
  const NUTRITION_LABEL_TYPE_FIELDS = 'fields';

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'nutrition_label_type' => 'serialized',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['nutrition_label_type'] = [
      '#type' => 'select',
      '#title' => t('Nutrition Label storage type'),
      '#description' => t('Choose whether to store the label data as a serialized array (better general performance) or table fields (more flexibility, views integration).'),
      '#default_value' => $this->getSetting('nutrition_label_type'),
      '#options' => [
        static::NUTRITION_LABEL_TYPE_SERIALIZED => t('Serialized'),
        static::NUTRITION_LABEL_TYPE_FIELDS => t('Fields'),
      ],
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];

    $properties['brandName'] = DataDefinition::create('string')
      ->setLabel(t('Brand Name'));

    $properties['itemName'] = DataDefinition::create('string')
      ->setLabel(t('Item Name'));

    // @todo: Should these decimal places be a display setting?
    $properties['decimalPlacesForNutrition'] = DataDefinition::create('integer')
      ->setLabel(t('Decimal places for nutrition values'));

    $properties['decimalPlacesForDailyValues'] = DataDefinition::create('integer')
      ->setLabel(t('Decimal places for "% daily values"'));

    $properties['decimalPlacesForQuantityTextbox'] = DataDefinition::create('integer')
      ->setLabel(t('Decimal places for serving unit quantity'));

    $properties['nameBottomLink'] = DataDefinition::create('string')
      ->setLabel(t('Bottom Link Name'));

    $properties['nameBottomLink'] = DataDefinition::create('uri')
      ->setLabel(t('Bottom Link URL'));

    $properties['widthCustom'] = DataDefinition::create('string')
      ->setLabel(t('Custom Width'))
      ->setDescription(t('Specify <em>auto</em> or a specific number of pixels.'));

    $properties['valueServingUnitQuantity'] = DataDefinition::create('float')
      ->setLabel(t('Serving Unit Quantity'));

    $properties['valueServingSizeUnit'] = DataDefinition::create('string')
      ->setLabel(t('Serving Size Unit'));

    // @todo: refactor
    $nutritionValues = [
      'Calories' => 'Calories',
      'FatCalories' => 'Fat Calories',
      'TotalFat' => 'Total Fat',
      'SatFat' => 'Sat Fat',
      'TransFat' => 'Trans Fat',
      'PolyFat' => 'Poly Fat',
      'MonoFat' => 'Mono Fat',
      'Cholesterol' => 'Cholesterol',
      'Sodium' => 'Sodium',
      'TotalCarb' => 'Total Carbohydrates',
      'Fibers' => 'Fiber',
      'Sugars' => 'Sugar',
      'AddedSugars' => 'Added Sugars',
      'SugarAlcohol' => 'Sugar Alcohol',
      'Proteins' => 'Protein',
      'Potassium' => 'Potassium',
      'VitaminA' => 'Vitamin A',
      'VitaminC' => 'Vitamin C',
      'VitaminD' => 'Vitamin D',
      'Calcium' => 'Calcium',
      'Iron' => 'Iron',
    ];
    foreach ($nutritionValues as $key => $label) {
      $properties['value' . $key] = DataDefinition::create('float')
        ->setLabel($label);

      $properties['na' . $key] = DataDefinition::create('boolean')
        ->setLabel($label . ' ' . t('Not Applicable'));

      $properties['unit' . $key] = DataDefinition::create('string')
        ->setLabel($label . ' ' . t('Units'));
    }

    $properties['valueServingWeightGrams'] = DataDefinition::create('float')
      ->setLabel(t('Serving Weight in Grams'));

    $properties['valueServingPerContainer'] = DataDefinition::create('float')
      ->setLabel(t('Servings Per Container'));

    $type = $field_definition->getSetting('nutrition_label_type');
    if ($type !== NutritionLabelItem::NUTRITION_LABEL_TYPE_FIELDS) {
      foreach ($properties as &$property) {
        $property->setComputed(true)->setInternal(false);
      }
      $properties['serialized_value'] = DataDefinition::create('string')
        ->setLabel(t('Serialized Label Settings'))
        ->setInternal(true);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $type = $field_definition->getSetting('nutrition_label_type');
    if ($type === NutritionLabelItem::NUTRITION_LABEL_TYPE_FIELDS) {
      $schema = ['columns' => []];
      $props = static::propertyDefinitions($field_definition);
      foreach ($props as $name => $data_definition) {
        $col = [
          'description' => $data_definition->getLabel(),
        ];
        switch ($data_definition->getDataType()) {
          case 'uri':
            $col['type'] = 'varchar';
            $col['length'] = 2048;
            break;
          case 'integer':
            $col['type'] = 'int';
            break;
          case 'float':
            $col['type'] = 'float';
            break;
          case 'boolean':
            $col['type'] = 'int';
            $col['size'] = 'tiny';
            break;
          default:
            $col['type'] = 'varchar';
            $col['length'] = 255;
            break;
        }
        $schema['columns'][$name] = $col;
      }
      // @todo: determine if any indexes would be useful, or if that should be configurable.
      return $schema;
    }
    else { //if ($type === NutritionLabelItem::NUTRITION_LABEL_TYPE_SERIALIZED) {
      return [
        'columns' => [
          'serialized_value' => [
            'description' => t('Serialized array of label data.'),
            'type' => 'blob',
            'size' => 'big',
            'serialize' => TRUE,
          ],
        ],
      ];
    }
    throw new \Exception(t('Unknown nutrition label type: @type', ['@type' => $type]));
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'itemName';
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    //   SqlContentEntityStorage::loadFieldItems, see
    //   https://www.drupal.org/node/2414835
    if (isset($values['serialized_value']) && is_string($values['serialized_value'])) {
      $values = unserialize($values['serialized_value']);
    }
    elseif ($this->getSetting('nutrition_label_type') === self::NUTRITION_LABEL_TYPE_SERIALIZED) {
      if (!empty(array_filter($values))) {
        $values = ['serialized_value' => serialize($values)];
      }
      else {
        $values = [];
      }
    }
    parent::setValue($values, $notify);
  }

}
