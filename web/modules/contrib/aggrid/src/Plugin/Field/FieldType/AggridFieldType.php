<?php

namespace Drupal\aggrid\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'aggrid' field type.
 *
 * @FieldType(
 *   id = "aggrid",
 *   label = @Translation("ag-Grid JSON Field"),
 *   description = @Translation("ag-Grid JSON Field"),
 *   category = @Translation("Text"),
 *   default_widget = "aggrid_widget_type",
 *   default_formatter = "aggrid_formatter_type",
 * )
 */
class AggridFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['aggrid_id'] = DataDefinition::create('string')
      ->setLabel(t('ag-Grid Entity for Default'));

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('JSON data for ag-Grid'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => t('JSON data for ag-Grid'),
          'type' => 'text',
          'pgsql_type' => 'json',
          'mysql_type' => 'json',
        ],
        'aggrid_id' => [
          'description' => t('ag-Grid Entity ID'),
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    return $element;
  }

}
