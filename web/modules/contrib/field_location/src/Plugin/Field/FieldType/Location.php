<?php

namespace Drupal\field_location\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides "location" field type.
 *
 * @FieldType(
 *   id = "location",
 *   label = @Translation("Location"),
 *   default_widget = "location_widget_standard",
 *   default_formatter = "location_formatter_standard",
 * )
 */
class Location extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {
    return [
      'columns' => self::columns(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'googlemaps_library' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['googlemaps_library'] = [
      '#type' => 'googlemaps_autocomplete',
      '#title' => $this->t('Google Maps instance'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('googlemaps_library'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field) {
    $properties = [];

    foreach (self::columns() as $column => $definition) {
      $properties[$column] = DataDefinition::create('string')
        ->setLabel($definition['label'])
        ->setSetting('default_value', isset($definition['default']) ? $definition['default'] : '')
        ->setReadOnly(TRUE)
        ->setRequired(TRUE);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    // Location has no main property.
    return NULL;
  }

  /**
   * Get field columns.
   *
   * @return array[]
   *   Field columns.
   */
  private static function columns() {
    $columns = [];

    $columns['locality'] = [
      'type' => 'varchar',
      'label' => t('Locality'),
      'length' => 256,
      'default' => '',
      'not null' => FALSE,
    ];

    $columns['coordinates'] = [
      'type' => 'blob',
      'size' => 'big',
      'label' => t('Coordinates'),
    ];

    return $columns;
  }

}
