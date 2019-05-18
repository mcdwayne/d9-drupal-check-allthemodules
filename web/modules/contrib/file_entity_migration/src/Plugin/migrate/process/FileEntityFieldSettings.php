<?php

namespace Drupal\file_entity_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Configure field settings for file entities.
 *
 * @MigrateProcessPlugin(
 *   id = "file_entity_field_settings"
 * )
 */
class FileEntityFieldSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('type') == 'file_entity') {
      $value['target_type'] = 'media';
    }
    return $value;
  }

}
