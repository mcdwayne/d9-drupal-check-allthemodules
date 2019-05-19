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
 *   id = "wwaf_add_proto"
 * )
 */
class wwafAddProto extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $v = parse_url($value[0], PHP_URL_SCHEME) === null ?
      'http://' . $value[0] : $value[0];
    if ($v == 'http://') {
      return NULL;
    }
    return $v;
  }

}
