<?php

namespace Drupal\data_api;

// Globals will pull in mocked classes, exceptions, etc.
require_once dirname(__FILE__) . '/../../globals.php';

class DataMock extends Data {

  protected function entity_extract_ids($entity_type, $entity) {
    $type = NULL;
    if ($entity_type === 'user') {
      $type = 'user';
    }
    else if (isset($entity->type)) {
      $type = $entity->type;
    }
    else {
      throw new \EntityMalformedException("Missing bundle property on entity of type $entity_type.");
    }

    return array(NULL, NULL, $type);
  }

  protected function field_get_items($entity_type, $entity, $field_name, $langcode = NULL
  ) {
    global $language;
    $default = empty($language) ? 'und' : $language;
    $langcode = is_null($langcode) ? $default : $langcode;

    if (!isset($entity->{$field_name}[$langcode])) {
      $entity->{$field_name}[$langcode] = array();
    }

    return $entity->{$field_name}[$langcode];
  }

  protected function field_info_instances($entity_type = NULL, $bundle_name = NULL
  ) {
    global $entity;
    $info = array();
    if (is_object($entity)) {
      $temp = (array) clone $entity;
      $fields = array();
      foreach (array_keys($temp) as $property) {
        if (strpos($property, 'field_') === 0) {
          $fields[] = $property;
        }
      }

      $info = array_fill_keys($fields, array());
    }

    return $info;
  }

  protected function field_info_field($field_name) {
    return array(
      'type' => 'datetime',
      'settings' => array('tz_handling' => 'UTC'),
    );
  }

  protected function field_language($entity_type, $entity, $field_name = NULL, $langcode = NULL
  ) {
    global $language;

    return empty($language) ? 'und' : $language;
  }

  protected function date_get_timezone($handling, $timezone = '') {
    return 'UTC';
  }

  protected function date_get_timezone_db($handling, $timezone = '') {
    return 'UTC';
  }

  protected function date_type_format($type) {
    switch ($type) {
      case 'date':
        return "Y-m-d\TH:i:s";

      case 'datestamp':
        return "U";

      case 'datetime':
        return "Y-m-d H:i:s";

      case 'ical':
        return "Ymd\THis";
    }
  }
}
