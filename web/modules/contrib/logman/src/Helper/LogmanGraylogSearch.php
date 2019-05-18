<?php
/**
 * @file
 * WatchdogSearch class.
 * This file contains implementation of all functions needed for watchdog
 * log search.
 */

namespace Drupal\logman\Helper;

composer_manager_register_autoloader();
use \ElasticSearch\Client;

/**
 * Implements the search, filtering and statistics
 * function for drupal watchdog logs.
 */
class LogmanGraylogSearch implements LogmanSearchInterface {
  protected $searchKey;
  protected $type;
  protected $limit;
  protected $quantity = 5;
  protected $host;
  protected $port;
  protected $node;
  protected $gelfUrl;

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
    $this->host = \Drupal::config('logman.settings')->get('logman_gelf_host');
    $this->port = \Drupal::config('logman.settings')->get('logman_gelf_port');
    $this->node = \Drupal::config('logman.settings')->get('logman_gelf_node');
    $this->gelfUrl = "http://" . $this->host . ":" . $this->port . "/" . $this->node . "/message";
  }

  /**
   * A function to get the statistics based on gelf.
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
    // Get statistics from Graylog2/gelf.
    // Prepare the against parameter as per gelf.
    $gelf_against = '';
    switch ($against) {
      case 'type':
        $gelf_against = 'facility';
        break;

      case 'severity':
      default:
        $gelf_against = 'level';
    }

    // Prepare the query data.
    $query_data = array(
      'facets' => array(
        $gelf_against => array(
          'terms' => array(
            'field' => $gelf_against,
          ),
        ),
      ),
    );
    if (empty($url)) {
      $query_data['query'] = array(
        'match_all' => new stdClass(),
      );
    }
    else {
      $query_data['query'] = array(
        'term' => array(
          '_Request_uri' => $url,
        ),
      );
    }

    $es = Client::connection($this->gelfUrl);
    $result = $es->search($query_data);

    // Prepare statistics array.
    $statistics = array();
    if (!empty($result['facets'][$gelf_against]['terms'])) {
      foreach ($result['facets'][$gelf_against]['terms'] as $item) {
        $statistics[] = (object) array(
          $against => $item['term'],
          'count' => $item['count'],
        );
      }
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
    // Prepare query_data.
    $query_data = array();
    $filter = array();
    // Available search fields other
    // than full_message, facility and created_at
    // and their corresponding mapping.
    $available_search_fields = array(
      'severity' => 'level',
      'uid' => '_Uid',
      'location' => '_Request_uri',
    );

    // Apply the search criteria.
    // Add the search key if present.
    if (!empty($this->searchKey)) {
      $query_data['query'] = array(
        'query_string' => array(
          'default_field' => 'full_message',
          'query' => $this->searchKey,
        ),
      );
    }
    else {
      $query_data['query'] = array(
        'match_all' => new stdClass(),
      );
    }

    // Type.
    if (!empty($this->type)) {
      $filter[] = array(
        'term' => array(
          'facility' => $this->type,
        ),
      );
    }
    // All other params.
    if (is_array($params) && !empty($params)) {
      // Use date range and remove form array.
      // So that it doesn't gets processed later.
      // The date range is expected to be an array.
      $date_range = $params['date_range'];
      unset($params['date_range']);
      if (!empty($date_range)) {
        if (count($date_range) == 1) {
          $query_data['query'] = array(
            'range' => array(
              'created_at' => array(
                'gte' => $date_range[0],
              ),
            ),
          );
        }
        else {
          $query_data['query'] = array(
            'range' => array(
              'created_at' => array(
                'gte' => $date_range[0],
                'lte' => $date_range[1],
              ),
            ),
          );
        }
      }
      foreach ($params as $key => $value) {
        if (!empty($value) && in_array($key, array_keys($available_search_fields))) {
          $filter[] = array(
            'term' => array(
              $available_search_fields[$key] => $value,
            ),
          );
        }
      }
    }

    // Add the where criteria.
    if (!empty($filter)) {
      if (count($filter) == 1) {
        $query_data['filter'] = $filter;
      }
      else {
        $query_data['filter'] = array(
          'and' => $filter,
        );
      }
    }

    // Add sorting on timestamp.
    $query_data['sort'] = array(
      array(
        'created_at' => array(
          'order' => 'desc',
        ),
      ),
    );

    // Perform the count query.
    $es = Client::connection($this->gelfUrl);
    $result = $es->search($query_data);
    $total_count = $result['hits']['total'];
    // Unset the result array to free the memory.
    unset($result);

    // Add pagination.
    $offset = $this->addPagination($total_count);
    $query_data['from'] = $offset * $this->limit;
    $query_data['size'] = $this->limit;

    // Perform the final query.
    $es = Client::connection($this->gelfUrl);
    $result = $es->search($query_data);

    $matches = array();
    if (!empty($result['hits']['hits'])) {
      foreach ($result['hits']['hits'] as $row) {
        $matches[] = array(
          'wid' => $row['_id'],
          'type' => $row['_source']['facility'],
          'message' => $row['_source']['message'],
          'severity' => $row['_source']['level'],
          'uid' => $row['_source']['_Uid'],
          'location' => $row['_source']['_Request_uri'],
          'timestamp' => $row['_source']['created_at'],
          'variables' => '',
          'referer' => '',
        );
      }
    }

    // Return the result sets and matches.
    // @FIXME
// theme() has been renamed to _theme() and should NEVER be called directly.
// Calling _theme() directly can alter the expected output and potentially
// introduce security issues (see https://www.drupal.org/node/2195739). You
// should use renderable arrays instead.
// 
// 
// @see https://www.drupal.org/node/2195739
// return (object) array(
//       'result_sets' => ceil(intval($total_count) / $this->limit),
//       'pagination' => theme('pager', array('quantity' => $this->quantity)),
//       'matches' => $matches,
//     );

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
    // Prepare query_data.
    $query_data = array(
      'query' => array(
        'term' => array(
          '_id' => $log_id,
        ),
      ),
    );

    // Perform log detail query.
    $es = Client::connection($this->gelfUrl);
    $result = $es->search($query_data);
    $detail = NULL;
    if (!empty($result['hits']['hits'])) {
      $detail = array(
        'wid' => $result['hits']['hits'][0]['_id'],
        'type' => $result['hits']['hits'][0]['_source']['facility'],
        'message' => $result['hits']['hits'][0]['_source']['message'],
        'severity' => $result['hits']['hits'][0]['_source']['level'],
        'uid' => $result['hits']['hits'][0]['_source']['_Uid'],
        'location' => $result['hits']['hits'][0]['_source']['_Request_uri'],
        'timestamp' => $result['hits']['hits'][0]['_source']['created_at'],
        'hostname' => $result['hits']['hits'][0]['_source']['host'],
        'variables' => '',
        'referer' => '',
        'link' => '',
      );
    }
    return $detail;
  }

  /**
   * Function to get the logged log types in watchdog.
   *
   * @return array
   *   An array of watchdog log types.
   */
  public static function getLogTypes() {
    // Get the types from graylog2.
    // Prepare query data array.
    $query_data = array(
      'query' => array(
        'match_all' => new stdClass(),
      ),
      'facets' => array(
        'facility' => array(
          'terms' => array(
            'field' => 'facility',
          ),
        ),
      ),
    );

    $gelf_url = "http://" . \Drupal::config('logman.settings')->get('logman_gelf_host');
    $gelf_url .= ":" . \Drupal::config('logman.settings')->get('logman_gelf_port');
    $gelf_url .= "/" . \Drupal::config('logman.settings')->get('logman_gelf_node') . "/message";
    $es = Client::connection($gelf_url);
    $result = $es->search($query_data);

    // Prepare log types array.
    $log_types = array();
    if (!empty($result['facets']['facility']['terms'])) {
      foreach ($result['facets']['facility']['terms'] as $item) {
        $log_types[$item['term']] = ucwords($item['term']);
      }
    }

    return $log_types;
  }

  /**
   * Function to add pagination to search result.
   *
   * @param int $total_count
   *   Total count of search result.
   *
   * @return int
   *   Starting offset for a page.
   */
  protected function addPagination($total_count) {
    global $pager_page_array, $pager_total, $pager_total_items;
    $page = isset($_GET['page']) ? $_GET['page'] : '';

    // Convert comma-separated $page to an array, used by other functions.
    $pager_page_array = explode(',', $page);

    $pager_total_items[0] = $total_count;
    $pager_total[0] = ceil($pager_total_items[0] / $this->limit);
    $pager_page_array[0] = max(0, min((int) $pager_page_array[0], ((int) $pager_total[0]) - 1));
    return $pager_page_array[0];
  }

  /**
   * Function to apply case insensitive search.
   *
   * This function will reset the graylog2 ES mapping,
   * which will actually delete all the current logged data.
   */
  public static function applyCaseInsensitiveSearch() {
    $gelf_url = "http://" . \Drupal::config('logman.settings')->get('logman_gelf_host');
    $gelf_url .= ":" . \Drupal::config('logman.settings')->get('logman_gelf_port');
    $gelf_url .= "/" . \Drupal::config('logman.settings')->get('logman_gelf_node') . "/message";
    $es = Client::connection($gelf_url);

    // Delete the current data and mapping.
    // Reset graylog2 for case insensitive search.
    // This will remove all the current logs.
    $es->request('', 'DELETE');

    // Add new mapping for case insensitivity.
    $mapping = array(
      'message' => array(
        'properties' => array(
          'full_message' => array(
            'type' => 'string',
            'analyzer' => 'standard',
          ),
          'level' => array(
            'type' => 'long',
          ),
          'facility' => array(
            'type' => 'string',
            'index' => 'not_analyzed',
            'omit_norms' => 'true',
            'index_options' => 'docs',
          ),
          '_Uid' => array(
            'type' => 'long',
          ),
          '_Server_host' => array(
            'type' => 'string',
            'index' => 'not_analyzed',
            'omit_norms' => 'true',
            'index_options' => 'docs',
          ),
          '_Request_uri' => array(
            'type' => 'string',
            'index' => 'not_analyzed',
            'omit_norms' => 'true',
            'index_options' => 'docs',
          ),
          '_Referer' => array(
            'type' => 'string',
            'index' => 'not_analyzed',
            'omit_norms' => 'true',
            'index_options' => 'docs',
          ),
          'created_at' => array(
            'type' => 'double',
          ),
        ),
      ),
    );
    $es->request('_mapping', 'PUT', $mapping);
  }
}
