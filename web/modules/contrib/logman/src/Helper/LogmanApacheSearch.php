<?php
/**
 * @file
 * ApacheSearch class.
 * This file contains implementation of all functions needed for apache access
 * log search.
 */

namespace Drupal\logman\Helper;

/**
 * Implements the search, filtering and statistics
 * function for apache access log.
 */
class LogmanApacheSearch implements LogmanSearchInterface {
  protected $apacheAccessLogPath;
  protected $limit;
  protected $readLimit = 100000;
  protected $filteredData;

  // Array element delete marker for array processing.
  protected $deleteMarker = 'DELETE';

  /**
   * Constructor for initializing the class variables.
   *
   * @param string $apache_access_log_path
   *   Apache access log path.
   * @param int $limit
   *   Number of log items on a page.
   */
  public function __construct($apache_access_log_path, $limit = 5) {
    $this->apacheAccessLogPath = $apache_access_log_path;
    $this->limit = $limit;
  }

  /**
   * A function to get the statistics based on the watchdog log.
   *
   * @param string $url
   *   URL of a page.
   * @param string $against
   *   Field value on which statistics date to be grouped.
   * @param array $date_range
   *   The date range value on which data will be fetched.
   *
   * @return array
   *   An array of statistics data.
   */
  public function getStatistics($url = '', $against = 'response_code', $date_range = array()) {
    $statistics = array();
    $data = $this->readApacheLog();
    // If statistics is against response code change it
    // actual key code i.e. 'code' for apache log data array.
    if ($against == 'response_code') {
      $against = 'code';
    }

    // Iterate through the whole apache data log array to
    // get the statistics.
    foreach ($data as $data_value) {
      if (!empty($data_value[$against])) {
        if (isset($statistics[$data_value[$against]])) {
          $statistics[$data_value[$against]]++;
        }
        else {
          $statistics[$data_value[$against]] = 1;
        }
      }
    }
    return $statistics;
  }

  /**
   * Function to set limit.
   *
   * @param int $limit
   *   Number of log items on a page.
   */
  public function setLimit($limit) {
    $this->limit = $limit;
  }

  /**
   * Function to set read limit.
   *
   * @param int $read_limit
   *   Number of characters to  be read from apache access log file.
   */
  public function setReadLimit($read_limit) {
    $this->readLimit = $read_limit;
  }

  /**
   * Function to check the apache log path.
   *
   * @return bool
   *   Returns true or false accordingly if apache access log exists.
   */
  public function checkApacheLogPath() {
    if (!empty($this->apacheAccessLogPath) && file_exists($this->apacheAccessLogPath)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * A function to search log on all fields of watchdog.
   *
   * @param array $params
   *   An array of field options.
   *
   * @return object
   *   Object of filtered log data.
   */
  public function searchLog($params = array()) {
    $total_count = 0;
    $this->filteredData = $this->readApacheLog();
    $option = $this->trimOption($params);
    if (!empty($this->filteredData)) {
      // Mark and filter the apache log data array.
      array_walk_recursive($this->filteredData, array($this, 'filterMarkApacheLog'), $option);
      array_walk($this->filteredData, array($this, 'filterApacheLog'));
      $this->filteredData = array_filter($this->filteredData);
      $total_count = count($this->filteredData);

      if ($total_count > 0) {
        // Rearrange and paginate the array.
        $this->filteredData = array_reverse($this->filteredData);
        $this->filteredData = $this->pagerArraySplice($this->filteredData);
      }
    }
    return (object) array(
      'data' => $this->filteredData,
      'totalCount' => $total_count,
    );
  }

  /**
   * Function to process mark apache log array items for filtering.
   *
   * @param mixed $item
   *   An array item.
   * @param mixed $key
   *   Key for the array item.
   * @param array $options
   *   Array of fields for filtering.
   */
  protected function filterMarkApacheLog(&$item, $key, $options) {
    if ($key == 'time') {
      $log_time = $this->getApacheLogTimeStamp($item);
      if ((!empty($options['date_from']) && $log_time < strtotime($options['date_from'])) ||
          (!empty($options['date_to']) && $log_time > strtotime($options['date_to']))) {
        $item = $this->deleteMarker;
      }
    }
    else {
      if (!empty($options[$key]) && !preg_match($options[$key], $item)) {
        $item = $this->deleteMarker;
      }
    }
  }

  /**
   * Function to filter apache log data based marked items.
   *
   * @param mixed $item
   *   An array item.
   * @param mixed $key
   *   Key for the array item.
   */
  protected function filterApacheLog(&$item, $key) {
    if (in_array($this->deleteMarker, $item)) {
      // Remove the data set from array
      // by setting it as NULL.
      $item = NULL;
    }
  }

  /**
   * Function to read apache access log.
   *
   * @return array|FALSE
   *   Array of log data or false.
   */
  protected function readApacheLog() {
    // Get the apache access log size.
    $apache_log_size = filesize($this->apacheAccessLogPath);
    // Set the file read offset so that the latest <readLimit> number
    // of characters are read from the file.
    if ($apache_log_size > $this->readLimit) {
      $read_offset = $apache_log_size - $this->readLimit;
    }
    else {
      $read_offset = NULL;
    }
    $dump = file_get_contents($this->apacheAccessLogPath, FALSE, NULL, $read_offset, $this->readLimit);
    if (!empty($dump)) {
      $lines = explode("\n", $dump);
      $data = array();
      foreach ($lines as $line) {
        $result = array();
        $line = trim($line);
        if (empty($line)) {
          // Avoiding empty apache log lines.
          continue;
        }
        $pattern = '/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+)( (\".*?\") (\".*?\"))?$/';
        preg_match($pattern, $line, $result);
        if (!empty($result)) {
          $agent = '';
          // Get the browsing agent information.
          if (!empty($result[14])) {
            $agent = explode(' ', trim($result[14], '"'));
            if (!empty($agent[0])) {
              $agent = $agent[0];
            }
          }
          $data[] = array(
            'ip' => $result[1],
            'time' => $result[4] . ' ' . $result[5] . ' ' . $result[6],
            'method' => $result[7],
            'url' => $result[8],
            'code' => $result[10],
            'agent' => $agent,
          );
        }
      }
      return $data;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Sanitise the search options for query.
   *
   * @param array $option
   *   An array of field options.
   *
   * @return mixed
   *   An array of fields converted to regex.
   */
  protected function trimOption($option) {
    foreach ($option as $key => $val) {
      if (empty($val)) {
        unset($option[$key]);
      }
      elseif ($key == 'date_from' || $key == 'date_to') {
        continue;
      }
      else {
        // Preparing pattern for preg_match.
        $val = preg_quote($val, '/');
        // Un escaping *
        $val = str_replace('\*', '.*', $val);
        $option[$key] = '/' . $val . '/';
      }
    }
    return $option;
  }

  /**
   * Function to extract a portion of array for pagination.
   *
   * @param array $data
   *   Apache full log data.
   *
   * @return array
   *   Apache log data sliced.
   */
  protected function pagerArraySplice($data) {
    global $pager_page_array, $pager_total, $pager_total_items;
    $page = isset($_GET['page']) ? $_GET['page'] : '';

    // Convert comma-separated $page to an array, used by other functions.
    $pager_page_array = explode(',', $page);

    // We calculate the total of pages as ceil(items / limit).
    $pager_total_items[0] = count($data);
    $pager_total[0] = ceil($pager_total_items[0] / $this->limit);
    $pager_page_array[0] = max(0, min((int) $pager_page_array[0], ((int) $pager_total[0]) - 1));
    return array_slice($data, $pager_page_array[0] * $this->limit, $this->limit, TRUE);
  }

  /**
   * Function to format apache log timestamp to PHP Y-m-d H:i:s format.
   *
   * @param string $log_time
   *   Apache access log entry timestamp.
   *
   * @return string
   *   An PHP/unix timestamp.
   */
  protected function getApacheLogTimeStamp($log_time) {
    $log_time_parts = explode(' ', $log_time);
    $date = $log_time_parts[0];
    $time = $log_time_parts[1];
    list($day, $month, $year) = explode('/', $date);
    return strtotime($year . '-' . date('m', strtotime($month)) . '-' . $day . ' ' . $time);
  }
}
