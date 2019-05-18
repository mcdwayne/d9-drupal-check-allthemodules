<?php

/**
 * @file
 * Support for migrate module
 */

/**
 * Implement hook_migrate_api().
 */
function date_ymd_migrate_api() {
  return array('api' => 2);
}

class MigrateDateYMDFieldHandler extends MigrateFieldHandler {
  public function __construct() {
    $this->registerTypes(array('date_ymd'));
  }
  public function prepare($entity, array $field_info, array $instance, array $values) {
    $language = $this->getFieldLanguage($entity, $field_info, array());

    foreach($values as $delta => $value) {
      $item = array();
      $item['ymd'] = $value;
      $return[$language][$delta] = $item;
      $delta++;
    }

    return isset($return) ? $return : NULL;
  }
}

