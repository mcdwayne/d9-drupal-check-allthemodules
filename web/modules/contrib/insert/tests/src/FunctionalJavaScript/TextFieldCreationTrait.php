<?php

namespace Drupal\Tests\insert\FunctionalJavascript;

use Drupal\Core\Entity\Display\EntityDisplayInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a helper method for creating text fields.
 */
trait TextFieldCreationTrait {

  /**
   * Creates a new file field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param string (optional) $type
   *   Type of the text field.
   * @param array (optional) $storage_settings
   *   A list of field storage settings that will be added to the
   *   defaults.
   * @param array (optional) $field_settings
   *   A list of instance settings that will be added to the instance
   *   defaults.
   * @param array (optional) $widget_settings
   *   Widget settings to be added to the widget defaults.
   * @param array (optional) $formatter_settings
   *   Formatter settings to be added to the formatter defaults.
   * @param string (optional) $description
   *   A description for the field. Defaults to ''.
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createTextField($name, $type_name, $type = 'text_long', array $storage_settings = [], array $field_settings = [], array $widget_settings = [], array $formatter_settings = [], $description = '') {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => $type,
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality'])
        ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $type_name,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
      'description' => $description,
    ]);
    $field_config->save();

    /** @var EntityDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $type_name . '.default');

    $entity
      ->setComponent($name, [
        'type' => 'string_textarea',
        'settings' => $widget_settings,
      ])
      ->save();

    /** @var EntityDisplayInterface $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.' . $type_name . '.default');

    $entity
      ->setComponent($name, [
        'type' => 'basic_string',
        'settings' => $formatter_settings,
      ])
      ->save();

    return $field_config;
  }

}
