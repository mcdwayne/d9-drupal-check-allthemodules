<?php

/**
 * @file
 * This file handles status metrics
 */

/**
 * Returns modules table.
 */
function drupalmonitor_connector_status_listmodules() {
  foreach (\Drupal::moduleHandler()->getModuleList() as $module => $filename) {
    if (isset($data[$module])) {
      $info[$module] = $data[$module]->info;
    }
  }
  return $info;
  $query = "SELECT * FROM {system}";
  $result = db_query($query);
  $data = array();
  foreach ($result as $record) {
    $infos = unserialize($record->info);
    unset($record->info);
    $record->info = $infos;
    unset($record->info['explanation']);
    unset($record->info['description']);
    $data[$record->name] = get_object_vars($record);
  }
  return $data;
}

/**
 * Returns variable table.
 */
function drupalmonitor_connector_status_listvars() {
  $query = "SELECT * FROM {config}";
  $result = db_query($query);
  $data = array();
  foreach ($result as $record) {
    $data[$record->name] = unserialize($record->data);
  }
  return $data;
}
