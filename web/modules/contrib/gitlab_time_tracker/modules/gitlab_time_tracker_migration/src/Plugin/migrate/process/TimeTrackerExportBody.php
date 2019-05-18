<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;


/**
 * Provides a 'TimeTrackerExportBody' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "time_tracker_export_body"
 * )
 */
class TimeTrackerExportBody extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $value = reset($value);
    }

    if (strpos($value, ':clock') !== FALSE) {
      $comment = array_filter(
        array_map(
          function ($element) {
            return trim($element);
          },
          explode('|', $value)
        )
      );

      return array_pop($comment);
    }
    else {
      throw new MigrateSkipRowException();
    }
  }

}
