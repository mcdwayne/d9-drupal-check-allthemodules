<?php

namespace Drupal\contacts_events\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Plugin implementation of the 'booking_windows' field type.
 *
 * @FieldType(
 *   id = "booking_windows",
 *   label = @Translation("Booking windows"),
 *   description = @Translation("Booking windows for pricing."),
 *   category = @Translation("Events"),
 *   default_widget = "booking_windows",
 *   default_formatter = "booking_windows",
 *   list_class = "\Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItemList",
 *   constraints = {"ContactsEventsDateTimeFormat" = {"property" = "cut_off"}},
 *   cardinality = \Drupal\Core\Field\FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
 * )
 */
class BookingWindowsItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'datetime_type' => DateTimeItem::DATETIME_TYPE_DATE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setRequired(TRUE);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Label'))
      ->setRequired(TRUE);

    $properties['cut_off'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(new TranslatableMarkup('Cut off'));

    $properties['date'] = DataDefinition::create('any')
      ->setLabel(t('Computed cut off date'))
      ->setDescription(t('The computed DateTime object.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'cut_off');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'id' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'label' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'cut_off' => [
          'description' => 'The cut off value.',
          'type' => 'varchar',
          'length' => 20,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    parent::applyDefaultValue($notify);
    $this->setValue(['label' => $this->t('Standard')], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['label'] = $random->word(mt_rand(1, 255));

    $type = $field_definition->getSetting('datetime_type');

    // Just pick a date in the coming year.
    $timestamp = \Drupal::service('time')->getRequestTime() + mt_rand(0, 86400 * 365);
    if ($type == DateTimeItem::DATETIME_TYPE_DATE) {
      $values['cut_off'] = gmdate(static::DATE_STORAGE_FORMAT, $timestamp);
    }
    else {
      $values['cut_off'] = gmdate(static::DATETIME_STORAGE_FORMAT, $timestamp);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['datetime_type'] = [
      '#type' => 'select',
      '#title' => t('Date type'),
      '#description' => t('Choose the type of date to create.'),
      '#default_value' => $this->getSetting('datetime_type'),
      '#options' => [
        DateTimeItem::DATETIME_TYPE_DATETIME => t('Date and time'),
        DateTimeItem::DATETIME_TYPE_DATE => t('Date only (cut off will be the end of the day)'),
      ],
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('label')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $values += [
      'cut_off' => NULL,
    ];
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Enforce that the computed date is recalculated.
    if ($property_name == 'cut_off') {
      $this->date = NULL;
    }
    parent::onChange($property_name, $notify);
  }

}
