<?php

namespace Drupal\Tests\imagefield_tokens\Functional;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a helper method for creating Image fields.
 */
trait ImageFieldTokensTestingTrait {

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   (optional) A list of field storage settings that will be added to the
   *   defaults.
   * @param array $field_settings
   *   (optional) A list of instance settings that will be added to the
   *   instance
   *   defaults.
   * @param array $widget_settings
   *   (optional) Widget settings to be added to the widget defaults.
   * @param array $formatter_settings
   *   (optional) Formatter settings to be added to the formatter defaults.
   * @param string $description
   *   (optional) A description for the field. Defaults to ''.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\field\Entity\FieldConfig
   *   Returns field config object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createImageFieldTokensField($name, $type_name, array $storage_settings = [], array $field_settings = [], array $widget_settings = [], array $formatter_settings = [], $description = '') {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
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

    $this->entityGetFormDisplay('node', $type_name, 'default')
      ->setComponent($name, [
        'type' => 'imagefield_tokens',
        'settings' => $widget_settings,
      ])
      ->save();

    $this->entityGetDisplay('node', $type_name, 'default')
      ->setComponent($name, [
        'type' => 'image',
        'settings' => $formatter_settings,
      ])
      ->save();

    return $field_config;
  }

  /**
   * Returns the entity view display associated with a bundle and view mode.
   *
   * Use this function when assigning suggested display options for a component
   * in a given view mode. Note that they will only be actually used at render
   * time if the view mode itself is configured to use dedicated display
   * settings for the bundle; if not, the 'default' display is used instead.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode, or 'default' to retrieve the 'default' display object for
   *   this bundle.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The entity view display associated with the view mode.
   *
   * @see \Drupal\Core\Entity\EntityStorageInterface::create()
   * @see \Drupal\Core\Entity\EntityStorageInterface::load()
   */
  protected function entityGetDisplay($entity_type, $bundle, $view_mode) : EntityViewDisplayInterface {
    // Try loading the display from configuration.
    $display = EntityViewDisplay::load($entity_type . '.' . $bundle . '.' . $view_mode);

    // If not found, create a fresh display object. We do not primitively
    // create new entity_view_display configuration entries for each existing
    // entity type and bundle whenever a new view mode becomes available.
    // Instead, configuration entries are only created when a display object is
    // explicitly configured and saved.
    if (!$display) {
      $display = EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $view_mode,
        'status' => TRUE,
      ]);
    }

    return $display;
  }

  /**
   * Returns the entity form display associated with a bundle and form mode.
   *
   * The function reads the entity form display object from the current
   * configuration, or returns a ready-to-use empty one if no configuration
   * entry exists yet for this bundle and form mode. This streamlines
   * manipulation of entity form displays by always returning a consistent
   * object that reflects the current state of the configuration.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $form_mode
   *   The form mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The entity form display associated with the given form mode.
   *
   * @see \Drupal\Core\Entity\EntityStorageInterface::create()
   * @see \Drupal\Core\Entity\EntityStorageInterface::load()
   */
  protected function entityGetFormDisplay($entity_type, $bundle, $form_mode) : EntityFormDisplayInterface {

    // Try loading the entity from configuration.
    $entity_form_display = EntityFormDisplay::load($entity_type . '.' . $bundle . '.' . $form_mode);

    // If not found, create a fresh entity object. We do not primitively create
    // new entity form display configuration entries for each existing entity
    // type and bundle whenever a new form mode becomes available. Instead,
    // configuration entries are only created when an entity form display is
    // explicitly configured and saved.
    if (!$entity_form_display) {
      $entity_form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => $form_mode,
        'status' => TRUE,
      ]);
    }

    return $entity_form_display;
  }

}
