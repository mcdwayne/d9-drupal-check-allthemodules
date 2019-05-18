<?php
/**
 * @file
 * WatchdogSearch class.
 * This file contains implementation of all functions needed for watchdog
 * log search.
 */

namespace Drupal\logman\Helper;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;

/**
 * Implements the search, filtering and statistics
 * function for drupal watchdog logs.
 */
class LogmanDblogSearch implements LogmanSearchInterface {
  protected $searchKey;
  protected $type;
  protected $limit;
  protected $quantity = 5;

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
    $this->searchKey = $search_key;
    $this->type = $type;
    $this->limit = $limit;
  }

  /**
   * A function to get the statistics based on dblog.
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
    // Basic sql query to fetch statistics.
    $query_tok_values = array();
    $sql  = "SELECT $against, COUNT(*) as 'count' FROM {watchdog}";

    $sql_criteria = '';

    // Filter on url if it is mentioned.
    if (!empty($url)) {
      $sql_criteria .= " location like '%$url' ";
    }

    // Add the date range if it is mentioned.
    if (!empty($date_range)) {
      $sql_criteria .= ' AND timestamp >= ' . implode(' AND timestamp <= ', $date_range);
    }

    // Add the criteria to the sql query.
    if (!empty($sql_criteria)) {
      $sql .= ' WHERE ' . $sql_criteria;
    }

    // Add the grouping and sorting.
    $sql .= " GROUP BY $against ORDER BY $against ";

    // Prepare statistics array.
    $result = Database::getConnection('default')->query($sql, $query_tok_values);
    $statistics = array();
    while ($row = $result->fetchObject()) {
      $statistics[] = $row;
    }
    return $statistics;
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
    $this->limit = $limit;
  }

  /**
   * Function to set quantity.
   *
   * @param int $quantity
   *   Number of pagination items on a page.
   */
  public function setQuantity($quantity) {
    $this->quantity = $quantity;
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
    // Basic sql search query with place holders.
    $query = Database::getConnection('default')->select('watchdog', 'w');

    // Apply the search criteria.
    // Search key.
    if (!empty($this->searchKey)) {
      $search_key = '%' . $this->searchKey . '%';
      $filter_by_key = db_or()->condition('message', $search_key, 'like')
                              ->condition('variables', $search_key, 'like');
      $query->condition($filter_by_key);
    }

    // Type.
    if (!empty($this->type)) {
      $query->condition('type', $this->type, '=');
    }
    // All other params.
    if (is_array($params) && !empty($params)) {
      // Use date range and remove form array.
      // So that it doesn't gets processed later.
      // The date range is expected to be an array.
      $date_range = $params['date_range'];
      unset($params['date_range']);
      if (!empty($date_range)) {
        $query->condition('timestamp', current($date_range), '>=');
        next($date_range);
        $query->condition('timestamp', current($date_range), '<=');
      }
      foreach ($params as $key => $value) {
        if (!empty($value)) {
          $query->condition($key, $value, '=');
        }
      }
    }

    $count_query = clone $query;
    $count_query->addExpression('Count(1)');
    $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $paged_query->limit($this->limit);
    $paged_query->setCountQuery($count_query);
    $result = $paged_query->fields('w')
      // Add sorting on timestamp.
      ->orderBy('timestamp', 'DESC')
      ->execute();

    $matches = array();
    while ($row = $result->fetchAssoc()) {
      $matches[] = $row;
    }

    // Return the result sets and matches.
    $total_result_count = $count_query->execute()->fetchField();
    $result_sets = ceil($total_result_count / $this->limit);
    $pagination_params = array('search_key' => $this->searchKey, 'log_type' => $this->type) + $params;
    $pager = array('#type' => 'pager', $pagination_params);
    $pagination = \Drupal::service('renderer')->render($pager);

    return (object) array(
      'result_sets' => $result_sets,
      'pagination' => $pagination,
      'matches' => $matches,
    );
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
    $query = Database::getConnection('default')->select('watchdog', 'w');
    $result = $query->fields('w')
      ->condition('wid', $log_id, '=')
      ->execute();
    if ($row = $result->fetchAssoc()) {
      return $row;
    }
    else {
      return NULL;
    }
  }

  /**
   * Function to get the logged log types in watchdog.
   *
   * @return array
   *   An array of watchdog log types.
   */
  public static function getLogTypes() {
    // Get the types in watchdog table.
    $log_types = array();
    $sql = "SELECT distinct type FROM {watchdog}";
    $result = Database::getConnection('default')->query($sql);
    while ($row = $result->fetchObject()) {
      $log_types[$row->type] = ucwords($row->type);
    }

    return $log_types;
  }
}
