<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Provides a 'TimeTrackerExtractDate' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "time_tracker_extract_date"
 * )
 */
class TimeTrackerExtractDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $comment_date = \DateTime::createFromFormat("U", strtotime($value[1]))->format('Y-m-d');
    if (strpos($value[0], 'clock') !== FALSE) {
      $comment = array_filter(
        array_map(
          function ($element) {
            return trim($element);
          },
          explode('|', $value[0])
        )
      );

      if ($date = \DateTimeImmutable::createFromFormat('Y-m-d', $comment[1])) {
        return $date->format('Y-m-d');
      }
      else {
        return $comment_date;
      }

    }

    return $comment_date;;
  }

}
