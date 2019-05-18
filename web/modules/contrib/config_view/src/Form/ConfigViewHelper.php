<?php

namespace Drupal\config_view\Form;

/**
 * Helper methods for adding custom behavior to the view and custom form.
 */
class ConfigViewHelper {

  /**
   * Retrieves mapping for give Config Entity.
   *
   * @param string $id
   *   Config Entity id.
   *
   * @return array|null
   *   Returns array of Entity attributes.
   */
  public static function getMapping($id) {
    $config_type_all = self::getConfigTypedDefinitions();
    $config_prefix = self::getConfigPrefix($id);

    foreach ($config_type_all as $key => $value) {

      if (isset($value['type']) && $value['type'] == 'config_entity') {
        if (strpos($key, $config_prefix) === 0) {
          return (isset($value['mapping']) ? $value['mapping'] : NULL);
        }
      }
    }
    // Return an empty array to avoid PHP errors when field definitions havent
    // been loaded yet or there is no mapping for the config entity.
    return [];
  }

  /**
   * Gets provider and config prefix for a given Config Entity.
   *
   * @param string $id
   *   Config Entity id.
   *
   * @return array
   *   Returns two attributes: provider and config prefix.
   */
  protected static function getConfigPrefix($id) {
    return \Drupal::entityTypeManager()->getDefinition($id)->getConfigPrefix();
  }

  /**
   * Gets the config.typed service.
   *
   * @return mixed
   *   Definitions of config.typed service.
   */
  protected static function getConfigTypedDefinitions() {
    return \Drupal::service('config.typed')->getDefinitions();
  }

  /**
   * Gets the config data used in the view.
   *
   * @return mixed
   *   An associative array of config enity ids of on/off values used in the
   *   hook_views_data() determining which one will be displayed int he group
   *   drop down.
   */
  public static function getConfigEntities() {
    return \Drupal::config('config_view.settings')->get('data');
  }

  /**
   * Gets the Config Entity Label.
   *
   * @param string $id
   *   Config Entity iD.
   *
   * @return string
   *   Config Entity label used in the group list.
   */
  public static function getConfigLabel($id) {
    return (string) \Drupal::entityTypeManager()->getDefinition($id)->getLabel();
  }

  /**
   * Sets the field, filter, sort, and argument type for the View.
   *
   * @param array $properties
   *   Contains type and name.
   *
   * @return array
   *   Combines: field, filter, and argument.
   */
  public static function getFieldParameters($properties) {
    $ret_value = [];

    if (isset($properties['label']) && $properties['label'] == 'Permissions') {
      $ret_value = [
        'field' => 'standard',
        'filter' => 'config_view_permissions_filter',
        'argument' => 'string',
      ];
    }
    else {
      switch ($properties['type']) {
        case 'string':
        case 'label':
          $ret_value = [
            'field' => 'standard',
            'filter' => 'config_view_string_filter',
            'argument' => 'string',
          ];
          break;

        case 'integer':
          $ret_value = [
            'field' => 'numeric',
            'filter' => 'config_view_numeric_filter',
            'argument' => 'numeric',
          ];
          break;

        case 'boolean';
        case 'date':
          $ret_value = [
            'field' => $properties['type'],
            'filter' => $properties['type'],
            'argument' => $properties['type'],
          ];
          break;
      }
    }
    return $ret_value;
  }

  /**
   * Determines response type and returns string value of the object.
   *
   * @param object $arg
   *   Response object from the result set.
   *
   * @return null|string
   *   String representation of the response object.
   */
  public static function responseToString($arg) {

    switch (gettype($arg)) {
      case 'boolean':
      case 'integer':
      case 'string':
        return $arg;

      case 'array':
        return implode(',', $arg);

      case 'object':
      case 'resource':
        return (string) $arg;

      default:
        return NULL;
    }
  }

}
