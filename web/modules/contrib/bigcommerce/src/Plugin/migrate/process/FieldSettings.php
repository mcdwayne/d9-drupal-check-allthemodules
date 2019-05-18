<?php

namespace Drupal\bigcommerce\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Gets the field settings and puts them on the destination row.
 *
 * The settings for a field are on the row based on the type of field, the
 * import_type. The settings are in import_type_settings. This process plugin
 * simply moves the correct settings to the destination property, 'settings'.
 *
 * @MigrateProcessPlugin(
 *   id = "bigcommerce_field_settings"
 * )
 */
class FieldSettings extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // If some fields requires special settings, process them.
    $import_type = $row->getSourceProperty('import_type');
    $settings = $row->getSourceProperty($import_type . '_settings');
    $row->setDestinationProperty('settings', $settings);
  }

}
