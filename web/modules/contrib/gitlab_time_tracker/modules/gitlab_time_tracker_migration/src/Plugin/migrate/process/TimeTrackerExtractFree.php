<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'TimeTrackerExtractFree' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "time_tracker_extract_free"
 * )
 */
class TimeTrackerExtractFree extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (is_array($value)) {
      $value = reset($value);
    }

    return strpos($value, ':clock') !== FALSE && strpos($value, ':free:') !== FALSE;
  }

}
