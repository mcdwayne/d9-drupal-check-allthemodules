<?php
/**
 * @file
 * SearchInterface.
 * Defines interface for performing log search.
 */

namespace Drupal\logman\Helper;

interface LogmanSearchInterface {
  /**
   * A function to get log statistics.
   *
   * @params string $url
   *   Page URL.
   * @params string $against
   *   Field value on which statistics data to be grouped.
   * @params array $date_range
   *   The date range value on which data will be fetched.
   */
  public function getStatistics($url, $against, $date_range);

  /**
   * A function to search logs.
   *
   * @params array $params
   *   Additional log fields to search.
   */
  public function searchLog($params = array());
}
