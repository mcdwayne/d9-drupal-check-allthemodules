<?php

namespace Drupal\contacts_events\Plugin\Field\FieldType;

use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'price_map' field type.
 *
 * @FieldType(
 *   id = "price_map",
 *   label = @Translation("Price map"),
 *   description = @Translation("Flexible pricing map."),
 *   category = @Translation("Events"),
 *   default_widget = "price_map",
 *   default_formatter = "price_map",
 *   list_class = "\Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItemList",
 *   cardinality = \Drupal\Core\Field\FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
 * )
 */
class PriceMapItem extends PriceItem {

  /**
   * Temporary delta tracking.
   *
   * @var int
   */
  protected $delta;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['booking_window'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Booking window'))
      ->setRequired(FALSE);

    $properties['class'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Class'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['booking_window'] = [
      'description' => 'The booking window.',
      'type' => 'varchar',
      'length' => 255,
    ];
    $schema['columns']['class'] = [
      'description' => 'The class.',
      'type' => 'varchar',
      'length' => 255,
    ];
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'order_item_type' => NULL,
      'booking_window_field' => NULL,
      'class_field' => NULL,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $element['order_item_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Context'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('order_item_type'),
      '#options' => [],
    ];

    $order_item_types = \Drupal::service('entity_type.manager')
      ->getStorage('commerce_order_item_type')
      ->loadMultiple();
    foreach ($order_item_types as $type) {
      $element['order_item_type']['#options'][$type->id()] = $type->label();
    }

    $element['booking_window_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Booking window field'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('booking_window_field'),
      '#options' => [],
    ];

    $element['class_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Class field'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('class_field'),
      '#options' => [],
    ];

    $field_definitions = $this->getEntity()->getFieldDefinitions();
    foreach ($field_definitions as $field_definition) {
      switch ($field_definition->getType()) {
        case 'booking_windows':
          $element['booking_window_field']['#options'][$field_definition->getName()] = $field_definition->getLabel();
          break;

        case 'entity_reference':
          if ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'contacts_events_class') {
            $element['class_field']['#options'][$field_definition->getName()] = $field_definition->getLabel();
          }
          break;
      }
    }

    return $element + parent::fieldSettingsForm($form, $form_state);
  }

  /**
   * Get the booking window ID.
   *
   * @return string
   *   The booking window ID.
   */
  public function getBookingWindow() {
    return $this->get('booking_window')->getValue();
  }

  /**
   * Get the class ID.
   *
   * @return string
   *   The class ID.
   */
  public function getClass() {
    return $this->get('class')->getValue();
  }

}
