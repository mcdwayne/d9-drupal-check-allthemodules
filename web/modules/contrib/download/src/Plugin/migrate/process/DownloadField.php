<?php

namespace Drupal\download\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Maps D7 download values to D8 download values.
 *
 * @MigrateProcessPlugin(
 *   id = "download_field"
 * )
 */
class DownloadField extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $fields = array('field_resourcefiles', 'field_spanish_files', 'field_representative_image');
    $download_fields = array_fill_keys($fields, '0');

    $parsed = [
      'download_label' => $value['download_label'],
    ];

    if (!empty($value['download_fields'])) {
      $active_fields = explode(';', $value['download_fields']);

      foreach($fields as $field) {
        if (in_array($field, $active_fields)) {
          $download_fields[$field] = "$field";
        }
      }
    }
    $parsed['download_fields'] = serialize($download_fields);
    return $parsed;
  }

}
