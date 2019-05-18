<?php

namespace Drupal\gitlab_time_tracker_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Provides a 'TimeTrackerParser' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "time_tracker_parser"
 * )
 */
class TimeTrackerParser extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $time = 0;

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

      $dates = explode(' ', $comment[0]);

      foreach ($dates as $date_part) {
        $match = [];

        if (preg_match_all('/(\d+)(h|m|d|w)/', $date_part, $match)) {
          switch ($match[2][0]) {
            case 'h':
              $time += $match[1][0] * 60;
              break;

            case 'm':
              $time += $match[1][0];
              break;

            case 'd':
              $time += $match[1][0] * 60 * 24;
              break;

            case 'w':
              $time += $match[1][0] * 60 * 24 * 7;
              break;
          }
        }

      }

      return $time;
    }
    else {
      throw new MigrateSkipRowException();
    }
  }
}
