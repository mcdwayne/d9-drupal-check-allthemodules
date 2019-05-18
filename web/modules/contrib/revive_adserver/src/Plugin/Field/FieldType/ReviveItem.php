<?php

namespace Drupal\revive_adserver\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the Revive adserver field type.
 *
 * @FieldType(
 *   id = "revive_adserver_zone",
 *   label = @Translation("Revive Adserver Zone"),
 *   description = @Translation("Show a Revive Adserver Zone as a field."),
 *   default_widget = "revive_adserver_zone",
 *   default_formatter = "revive_adserver_zone"
 * )
 */
class ReviveItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['zone_id'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Revive Adserver Zone Id'));
    $properties['invocation_method'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Banner invocation method.'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'zone_id' => [
          'description' => 'Revive Zone Id.',
          'type' => 'int',
          'default' => NULL,
        ],
        'invocation_method' => [
          'description' => 'Banner invocation method.',
          'type' => 'varchar',
          'default' => NULL,
          'length' => 255,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['invocation_method_per_entity' => FALSE] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element['invocation_method_per_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select invocation method per entity.'),
      '#default_value' => $this->getSetting('invocation_method_per_entity'),
      '#description' => $this->t('You will be able to select the invocation method for each entity.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $isEmpty =
      empty($this->get('zone_id')->getValue()) &&
      empty($this->get('invocation_method')->getValue());

    return $isEmpty;
  }

}
