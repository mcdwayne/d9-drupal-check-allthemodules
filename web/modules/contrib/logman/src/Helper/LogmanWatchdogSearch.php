<?php
/**
 * @file
 * WatchdogSearch class.
 * This file contains implementation of all functions needed for watchdog
 * log search.
 */

namespace Drupal\logman\Helper;

use Drupal\logman\HelperLogmanDblogSearch;
use Drupal\logman\Helper\LogmanGraylogSearch;

/**
 * Implements the search, filtering and statistics
 * function for drupal watchdog logs.
 */
class LogmanWatchdogSearch implements LogmanSearchInterface {
  protected $logType;
  protected $logObject;

  /**
   * Constructor for initializing the class variables.
   *
   * @param null $search_key
   *   Value to search.
   * @param null $type
   *   Log type to search.
   * @param int $limit
   *   Number of log items on a page.
   */
  public function __construct($search_key = NULL, $type = NULL, $limit = 5) {
    $this->logType = \Drupal::config('logman.settings')->get('logman_watchdog_log_type');
    // Based on the log type create the log object accordingly.
    if ($this->logType == 'dblog') {
      $this->logObject = new LogmanDblogSearch($search_key, $type, $limit);
    }
    else {
      $this->logObject = new LogmanGraylogSearch($search_key, $type, $limit);
    }
  }

  /**
   * A function to get the statistics based on the watchdog log.
   *
   * @param string $url
   *   Page URL.
   * @param string $against
   *   Field value on which statistics data to be grouped.
   * @param array $date_range
   *   The date range value on which data will be fetched.
   *
   * @return array
   *   An array of statistics data.
   */
  public function getStatistics($url = '', $against = 'severity', $date_range = array()) {
    return $this->logObject->getStatistics($url, $against, $date_range);
  }

  /**
   * A function to get the url wise page statistics based on the watchdog log.
   *
   * @param string $url
   *   Page URL.
   * @param array $date_range
   *   The date range value on which data will be fetched.
   *
   * @return array
   *   An array of statistics data.
   */
  public function getPageStatistics($url, $date_range = array()) {
    return $this->getStatistics($url, 'severity', $date_range);
  }

  /**
   * Function to set limit.
   *
   * @param int $limit
   *   Number of log items on a page.
   */
  public function setLimit($limit) {
    $this->logObject->setLimit($limit);
  }

  /**
   * Function to set quantity.
   *
   * @param int $quantity
   *   Number of pagination items on a page.
   */
  public function setQuantity($quantity) {
    $this->logObject->setQuantity($quantity);
  }

  /**
   * A function to search log on all fields of watchdog.
   *
   * @param array $params
   *   Additional log fields to search.
   *
   * @return object
   *   Object containing filtered log data.
   */
  public function searchLog($params = array()) {
    return $this->logObject->searchLog($params);
  }

  /**
   * Function to get watchdog log detail.
   *
   * @param int $log_id
   *   The watchdog log id.
   *
   * @return null|object
   *   Object containing detailed watchdog log data.
   */
  public function getLogDetail($log_id) {
    return $this->logObject->getLogDetail($log_id);
  }

  /**
   * Function to get the logged log types in watchdog.
   *
   * @return array
   *   An array of watchdog log types.
   */
  public static function getLogTypes() {
    // Get the log types in watchdog.
    if (\Drupal::config('logman.settings')->get('logman_watchdog_log_type') == 'dblog') {
      require_once 'LogmanDblogSearch.php';
      return LogmanDblogSearch::getLogTypes();
    }
    else {
      require_once 'LogmanGraylogSearch.php';
      return LogmanGraylogSearch::getLogTypes();
    }
  }
}
