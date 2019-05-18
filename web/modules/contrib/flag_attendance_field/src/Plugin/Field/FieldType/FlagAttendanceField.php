<?php

namespace Drupal\flag_attendance_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'flag_attendance' field type.
 *
 * @FieldType(
 *   id = "flag_attendance",
 *   label = @Translation("Attendance"),
 *   description = @Translation("This field store attendance for specific falg on content."),
 *   category = @Translation("Attendance"),
 *   default_widget = "attendance_default",
 * )
 */
class FlagAttendanceField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'description' => 'The attendance for this entity.',
          'type' => 'blob',
          'size' => 'big',
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string');

    return $properties;

  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    $entity = parent::getEntity();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $is_empty = empty($this->value);
    return $is_empty;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Flag settings.
    $falg_types = \Drupal::service('flag')->getAllFlags($this->getEntity()->getEntityTypeId(), $this->getEntity()->bundle());

    $flags = [];
    foreach ($falg_types as $key => $value) {
      $flags[$key] = $value->label();
    }

    $element = [];
    $element['flag'] = [
      '#title' => $this->t('Associated flag'),
      '#type' => 'select',
      '#options' => $flags,
      '#default_value' => $this->getSetting('flag'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'flag' => [],
    ];
  }

}
