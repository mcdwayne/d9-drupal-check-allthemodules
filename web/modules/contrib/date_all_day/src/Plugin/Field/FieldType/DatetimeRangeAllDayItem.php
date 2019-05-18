<?php

namespace Drupal\date_all_day\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Plugin implementation of the 'daterange' field type.
 *
 * @FieldType(
 *   id = "daterange_all_day",
 *   label = @Translation("Date time range (All day)"),
 *   description = @Translation("Create and store date ranges with all day option."),
 *   default_widget = "daterange_all_day",
 *   default_formatter = "daterange_all_day_default",
 *   list_class = "\Drupal\datetime_range\Plugin\Field\FieldType\DateRangeFieldItemList"
 * )
 */
class DatetimeRangeAllDayItem extends DateRangeItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'optional_end_date' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['value_all_day'] = [
      'description' => 'Stores if the start date is all day',
      'type' => 'int',
      'size' => 'tiny',
      'default' => 0,
    ];
    $schema['columns']['end_value_all_day'] = [
      'description' => 'Stores if the end date is all day',
      'type' => 'int',
      'size' => 'tiny',
      'default' => 0,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['value_all_day'] = DataDefinition::create('boolean')
      ->setLabel(t('All day'))
      ->setRequired(TRUE);
    $properties['end_value_all_day'] = DataDefinition::create('boolean')
      ->setLabel(t('All day'))
      ->setRequired(TRUE);

    $properties['end_value']->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->getFieldDefinition()->getFieldStorageDefinition()->getSetting('optional_end_date')) {
      $start_value = $this->get('value')->getValue();
      return $start_value === NULL || $start_value === '';
    }

    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $element['optional_end_date'] = [
      '#type' => 'checkbox',
      '#title' => t('Optional end date'),
      '#default_value' => $this->getSetting('optional_end_date'),
    ];

    return $element;
  }

}
