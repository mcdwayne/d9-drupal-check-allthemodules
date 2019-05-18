<?php

namespace Drupal\media_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure field instance settings for media image fields.
 *
 * @MigrateProcessPlugin(
 *   id = "media_image_field_instance_settings"
 * )
 */
class MediaImageFieldInstanceSettings extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($row->getSourceProperty('type') == 'media_image') {
      $value['handler_settings']['target_bundles'] = ['image'];
    }
    return $value;
  }

}
