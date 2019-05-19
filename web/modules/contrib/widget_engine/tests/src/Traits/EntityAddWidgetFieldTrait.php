<?php

namespace Drupal\Tests\widget_engine\Traits;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Provides methods to add widget field to some entity type.
 *
 * This trait is meant to be used only by test classes.
 */
trait EntityAddWidgetFieldTrait {

  /**
   * Add Entity reference field linked to Widget entity type.
   */
  public function entityAddWidgetField($entity_type, $bundle, $field_name, $field_label, $field_widget, $field_widget_settings = []) {
    // Add or remove the body field, as needed.
    $field_storage = FieldStorageConfig::loadByName('widget', $field_name);
    if (empty($field_storage)) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference',
        'cardinality' => '-1',
        'settings' => [
          'target_type' => 'widget',
        ],
      ]);
      $field_storage->save();
    }
    $field = FieldConfig::loadByName('widget', $bundle, $field_name);
    if (empty($field)) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $bundle,
        'label' => $field_label,
        'settings' => [
          'handler' => 'default',
        ],
      ]);
      $field->save();

      if (!$field_widget_settings) {
        $field_widget_settings = [
          'allow_existing' => TRUE,
        ];
      }

      // Assign widget settings for the 'default' form mode.
      \Drupal::entityTypeManager()->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.default')
        ->setComponent(
              $field_name, [
                'type' => $field_widget,
                'settings' => $field_widget_settings,
              ])
        ->save();

      // Assign display settings for the 'default' and 'teaser' view modes.
      \Drupal::entityTypeManager()->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.default')
        ->setComponent(
              $field_name, [
                'type' => 'entity_reference_entity_view',
              ])
        ->save();

      // The teaser view mode is created by the Standard profile and therefore
      // might not exist.
      $view_modes = \Drupal::entityManager()->getViewModes($entity_type);
      if (isset($view_modes['teaser'])) {
        \Drupal::entityTypeManager()->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.teaser')
          ->setComponent(
                $field_name, [
                  'label' => 'hidden',
                  'type' => 'entity_reference_entity_view',
                ])
          ->save();
      }
    }

    return $field;
  }

}
