<?php

/**
 * @file
 * Contains \Drupal\geofield\Plugin\migrate\process\GeofieldLatLon.
 */
namespace Drupal\wwaf_import\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process latitude and longitude and return the value for the D8 geofield.
 *
 * @MigrateProcessPlugin(
 *   id = "wwaf_type"
 * )
 */
class wwafType extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $type = \Drupal::config('wwaf_import.settings')->get('import_type');
    return $type;
  }

}
