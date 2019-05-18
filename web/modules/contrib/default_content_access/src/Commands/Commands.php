<?php

namespace Drupal\default_content_access\Commands;

use Drupal\default_content\Commands\DefaultContentCommands;

/**
 * @package Drupal\default_content_access
 */
class Commands extends DefaultContentCommands {

  /**
   * Exports all the content access setting defined in a module info file.
   *
   * @param string $module
   *   The name of the module.
   *
   * @command default-content-access:export-module
   * @aliases dcaem
   */
  public function contentExportModule($module) {
    if (!$this->isValidModule($module)) {
      return;
    }
    parent::contentExportModule($module);

    $serialized_by_type = $this->defaultContentExporter->exportModuleContent($module);
    $module_folder = \Drupal::moduleHandler()
      ->getModule($module)
      ->getPath() . '/access';
    file_prepare_directory($module_folder, FILE_CREATE_DIRECTORY);

    $file_name = $module_folder . DIRECTORY_SEPARATOR . 'node.json';
    if (file_exists($file_name)) {
      file_unmanaged_delete($file_name);
    }
    if (!empty($serialized_by_type['node'])) {
      $output = [];
      foreach ($serialized_by_type['node'] as $uuid => $value) {
        $query = \Drupal::database()->select('content_access', 'ca');
        $query->join('node', 'n', 'ca.nid = n.nid');
        $settings = $query->fields('ca', ['settings'])
          ->condition('n.uuid', $uuid)
          ->execute()
          ->fetchField();
        if (!empty($settings)) {
          $output[$uuid] = $settings;
        }
      }
      file_put_contents($file_name, json_encode($output));
    }
  }

  /**
   * Imports all the content access defined in a module info file.
   *
   * @param string $module
   *   The name of the module.
   *
   * @command default-content-access:import-module
   * @option update-existing Flag if existing content should be updated, defaults to FALSE.
   * @aliases dcaim
   */
  public function contentImportModule($module, $options = ['update-existing' => FALSE]) {
    if (!$this->isValidModule($module)) {
      return;
    }
    parent::contentImportModule($module, $options);

    $serialized_by_type = $this->defaultContentExporter->exportModuleContent($module);
    $module_folder = \Drupal::moduleHandler()
        ->getModule($module)
        ->getPath() . '/access';
    $file_name = $module_folder . DIRECTORY_SEPARATOR . 'node.json';
    if (!file_exists($file_name)) {
      return;
    }
    $settings = json_decode(file_get_contents($file_name), TRUE);
    $nids = \Drupal::database()->select('node', 'n')
      ->fields('n', ['uuid', 'nid'])
      ->condition('n.uuid', array_keys($serialized_by_type['node']), 'IN')
      ->execute()
      ->fetchAllKeyed(0, 1);
    \Drupal::database()->delete('content_access')
      ->condition('nid', array_values($nids), 'IN')
      ->execute();
    $query = \Drupal::database()->insert('content_access')
      ->fields(['nid', 'settings']);
    foreach ($settings as $uuid => $setting) {
      $query->values([$nids[$uuid], $setting]);
    }
    try {
      $query->execute();
    } catch (\Exception $e) {
    }
    node_access_rebuild();
  }

}
