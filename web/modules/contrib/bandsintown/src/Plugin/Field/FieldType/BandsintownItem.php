<?php

namespace Drupal\bandsintown\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'bandsintown' field type.
 *
 * @FieldType(
 *   id = "bandsintown",
 *   label = @Translation("Bandsintown"),
 *   module = "bandsintown",
 *   description = @Translation("Bandsintown field."),
 *   default_widget = "bandsintown",
 *   default_formatter = "bandsintown"
 * )
 */
class BandsintownItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $service = \Drupal::service('bandsintown.helper');
    $columns = array();
    $bandsintown_settings = $service->bandsintownSettings();
    foreach ($bandsintown_settings as $key => $setting) {
      $columns[$key] = array(
        'type'   => 'char',
        'length' => 255,
      );
    }
    return array(
      'columns' => $columns,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $service = \Drupal::service('bandsintown.helper');
    $bandsintown_settings = $service->bandsintownSettings();
    foreach ($bandsintown_settings as $key => $setting) {
      if ($this->get($key)->getValue()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $service = \Drupal::service('bandsintown.helper');
    $properties = array();
    $bandsintown_settings = $service->bandsintownSettings();
    foreach ($bandsintown_settings as $key => $setting) {
      $properties[$key] = DataDefinition::create($setting['type'])
        ->setLabel($setting['desc']);
    }
    return $properties;
  }

}
