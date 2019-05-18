<?php
namespace Drupal\map_object_field\Plugin\Field;

/**
 * Contains methods for map options.
 */
trait TMapOptions {

  /**
   * Returns array of map object types.
   */
  public function getMapObjectTypes() {
    return \Drupal::config('map_object_field.settings')->get('drawing_object_types');
  }

  /**
   * Returns array of map object types with labels.
   */
  public function getMapObjectTypesWithLabels() {
    $result = [];
    $drawing_object_types = \Drupal::config('map_object_field.settings')
      ->get('drawing_object_types');
    foreach ($drawing_object_types as $drawing_object_type) {
      $result[$drawing_object_type] = ucfirst($drawing_object_type);
    }
    return $result;
  }

  /**
   * Returns array of map types.
   */
  public function getMapTypes() {
    return \Drupal::config('map_object_field.settings')->get('map_types');
  }

  /**
   * Returns array of map types with labels.
   */
  public function getMapTypesWithLabels() {
    $result = [];
    $map_types = \Drupal::config('map_object_field.settings')->get('map_types');
    foreach ($map_types as $map_type) {
      $result[$map_type] = ucfirst($map_type);
    }
    return $result;
  }

}
