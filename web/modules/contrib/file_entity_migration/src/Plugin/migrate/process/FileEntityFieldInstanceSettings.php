<?php

namespace Drupal\file_entity_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure field instance settings for file entity fields.
 *
 * @MigrateProcessPlugin(
 *   id = "file_entity_field_instance_settings"
 * )
 */
class FileEntityFieldInstanceSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $type = $row->getSourceProperty('type');

    if ($type == 'file_entity') {
      $widget_settings = $row->getSourceProperty('widget');
      if (empty($widget_settings['settings']['allowed_types'])) {
        throw new MigrateSkipRowException('No target media bundle found for file_entity field ' . $row->getSourceProperty('field_name'));
      }
      $target_bundles = array_filter($widget_settings['settings']['allowed_types']);
      $value['handler_settings']['target_bundles'] = $target_bundles;
    }
    return $value;
  }

}
