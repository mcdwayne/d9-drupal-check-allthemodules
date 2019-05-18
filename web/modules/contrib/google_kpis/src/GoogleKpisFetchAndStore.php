<?php

namespace Drupal\google_kpis;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\google_kpis\Entity\GoogleKpis;
use Drupal\node\Entity\Node;
use Google_Client;
use Google_Exception;
use Google_Service_Webmasters;
use Google_Service_Webmasters_SearchAnalyticsQueryRequest;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Class GoogleAnalyticsFetchAndStore.
 *
 * This class is used for api query and database operations.
 */
class GoogleKpisFetchAndStore {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Google KPI Settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  public $googleKpisSettings;

  /**
   * The Google Analytics Reports Api Settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $googleAnalyticsReportsApiSettings;

  /**
   * Drupal logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Drupal queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Constructs a new GoogleAnalyticsFetchAndStore object.
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager, ConfigFactory $config_factory, LoggerChannelFactoryInterface $logger, QueueFactory $queue) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->googleKpisSettings = $config_factory->get('google_kpis.settings');
    $this->googleAnalyticsReportsApiSettings = $config_factory->get('google_analytics_reports_api.settings');
    $this->logger = $logger;
    $this->queue = $queue;
  }

  /**
   * Fetch data from GA.
   *
   * @return array
   *   All nodes with and without ga data.
   */
  public function fetchGoogleAnalyticsData() {
    /** @var \Drupal\google_analytics_reports_api\GoogleAnalyticsReportsApiFeed $gaReports */
    $gaReports = google_analytics_reports_api_gafeed();
    $start_date = $this->googleKpisSettings->get('ga_start_date');
    $end_date = $this->googleKpisSettings->get('ga_end_date');
    $profile_id = $this->googleAnalyticsReportsApiSettings->get('profile_id');
    $params = [
      'profile_id' => 'ga:' . $profile_id,
      'start_date' => strtotime($start_date),
      'end_date' => strtotime($end_date),
      'metrics' => 'ga:pageviews, ga:users, ga:sessions, ga:organicsearches',
      'dimensions' => 'ga:pagePath',
      'sort_metric' => '-ga:pageviews',
    ];
    try {
      $ga_query = $gaReports->queryReportFeed($params);
    }
    catch (Google_Exception $exception) {
      $this->logger('google_kpis')->error($exception->getMessage());
    }
    // Initialize variables.
    $ga_nodes = [];
    $diff_nodes = [];
    foreach ($ga_query->results->rows as $article) {
      // Trim url query paremeter from path.
      $pagePath = strtok($article['pagePath'], '?');
      if (strpos($pagePath, 'node') !== FALSE) {
        $node_id = filter_var($pagePath, FILTER_SANITIZE_NUMBER_INT);
      }
      else {
        // Get system path from path alias.
        $path_source = $this->database->select('url_alias', 'ua')->fields('ua', ['source'])->condition('alias', $pagePath)->execute()->fetchField();
        // Get node id from system path.
        $node_id = filter_var($path_source, FILTER_SANITIZE_NUMBER_INT);
      }
      if (is_numeric($node_id)) {
        if (!array_key_exists($node_id, $ga_nodes)) {
          $diff_nodes[$node_id] = $node_id;
          $ga_nodes[$node_id]['google_analytics_data'] = $article;
        }
        else {
          $ga_nodes[$node_id]['google_analytics_data'] = [
            'pageviews' => $ga_nodes[$node_id]['google_analytics_data']['pageviews'] + $article['pageviews'],
            'users' => $ga_nodes[$node_id]['google_analytics_data']['users'] + $article['users'],
            'sessions' => $ga_nodes[$node_id]['google_analytics_data']['sessions'] + $article['sessions'],
            'organicsearches' => $ga_nodes[$node_id]['google_analytics_data']['organicsearches'] + $article['organicsearches'],
          ];
        }
      }
    }
    // Get all published node ids.
    $published_nodes = $this->entityTypeManager->getStorage('node')
      ->getQuery('AND')
      ->condition('status', 1)
      ->execute();
    // Set value as keys.
    $published_nodes = $this->arraySetValueAsKey($published_nodes);
    // Get Articles that are not in GA report.
    $nodes_not_in_ga = array_diff($published_nodes, $diff_nodes);

    return array_replace($ga_nodes, $nodes_not_in_ga);
  }

  /**
   * Fetch data from search console api.
   *
   * @return array
   *   An assoc array of ALL nodes.
   */
  public function fetchGoogleSearchConsoleData() {
    $outside_webroot = $this->googleKpisSettings->get('outside_webroot');
    $path_to_auth_json = trim(strip_tags($this->googleKpisSettings->get('path_to_service_account_json')));
    $start_date = trim(strip_tags($this->googleKpisSettings->get('gsc_start_date')));
    $end_date = trim(strip_tags($this->googleKpisSettings->get('gsc_end_date')));
    $row_limit = $this->googleKpisSettings->get('gsc_row_limit');
    $site_url = $this->googleKpisSettings->get('gsc_prod_url');
    $app_name = $this->googleKpisSettings->get('gsc_application_name');
    if (is_null($row_limit) || $row_limit == 0) {
      $row_limit = 1000;
    }
    if ($outside_webroot) {
      // Fetch GoogleSearchConsole data and store it.
      putenv('GOOGLE_APPLICATION_CREDENTIALS=' . realpath($path_to_auth_json));
    }
    else {
      // Fetch GoogleSearchConsole data and store it.
      putenv('GOOGLE_APPLICATION_CREDENTIALS=' . getcwd() . $path_to_auth_json);
    }
    $client = new Google_Client();
    $client->setApplicationName($app_name);
    $client->useApplicationDefaultCredentials();
    $client->setScopes([Google_Service_Webmasters::WEBMASTERS_READONLY]);
    $service = new Google_Service_Webmasters($client);
    $query = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
    $query->setStartDate(date("Y-m-d", strtotime($start_date)));
    $query->setEndDate(date('Y-m-d', strtotime($end_date)));
    $query->setDimensions(['page']);
    $query->setRowLimit($row_limit);
    try {
      $data = $service->searchanalytics->query($site_url, $query);
    }
    catch (Google_Exception $exception) {
      $this->logger('google_kpis')->error($exception->getMessage());
    }
    $gsc_rows = $data->getRows();
    $gsc_nodes = [];
    $diff_nodes = [];
    foreach ($gsc_rows as $gsc_row) {
      // Get uri from url.
      $alias = str_replace($site_url, '', $gsc_row->getKeys()[0]);
      // Get system path from path alias.
      $path_source = $this->database->select('url_alias', 'ua')->fields('ua', ['source'])->condition('alias', $alias)->execute()->fetchField();
      // Get node id from system path.
      $node_id = filter_var($path_source, FILTER_SANITIZE_NUMBER_INT);
      if (is_numeric($node_id)) {
        if (!array_key_exists($node_id, $gsc_nodes)) {
          $diff_nodes[$node_id] = $node_id;
          $gsc_nodes[$node_id]['search_analytics_data'] = $gsc_row;
        }
      }
    }
    // Get all published Nodes.
    $published_nodes = $this->entityTypeManager->getStorage('node')->getQuery('AND')->condition('status', 1)->execute();
    $published_nodes = $this->arraySetValueAsKey($published_nodes);
    // Get Nodes that are not in GSC report.
    $nodes_not_in_gsc = array_diff($published_nodes, $diff_nodes);

    return array_replace($gsc_nodes, $nodes_not_in_gsc);
  }

  /**
   * Checks if the node has field_google_kpis, links to the google kpis entity.
   *
   * If node has field_google_kpis.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|GoogleKpis
   *   GoogleKpis object.
   */
  public function linkGoogleKpisWithNode(Node $node) {
    $gkids = $this->entityTypeManager->getStorage('google_kpis')->getQuery('AND')->condition('referenced_entity', $node->id())->execute();
    $gkid = reset($gkids);
    if ($gkid) {
      if ($node instanceof Node && $node->hasField('field_google_kpis')) {
        $field_value = $node->field_google_kpis->entity;
        if ($field_value && $field_value instanceof GoogleKpis) {
          $google_kpi = $field_value;
        }
        else {
          $google_kpi = $this->entityTypeManager->getStorage('google_kpis')->load($gkid);
          $node->set('field_google_kpis', $google_kpi->id());
          $node->save();
        }
      }
      else {
        $google_kpi = $this->entityTypeManager->getStorage('google_kpis')->load($gkid);
      }
    }
    else {
      $google_kpi = GoogleKpis::create([
        'name' => $node->getTitle(),
        'referenced_entity' => $node->id(),
      ]);
    }

    return $google_kpi;
  }

  /**
   * Queue entity operations into google_kpis_queue.
   *
   * @param array $data
   *   The combined data.
   */
  public function prepareQueue(array $data) {
    foreach ($data as $nid => $data) {
      $this->queue->get('google_kpis_queue')->createItem([
        'nid' => $nid,
        'data' => $data,
      ]);
    }
  }

  /**
   * Combine fetched data.
   *
   * @param array $analytics_data
   *   Google analytics data.
   * @param array $search_console_data
   *   Google search console data.
   *
   * @return array
   *   Returns the combined data.
   */
  public function combineAnalyticsAndSearchConsoleData(array $analytics_data, array $search_console_data) {
    foreach ($analytics_data as $nid => $data) {
      if (isset($search_console_data[$nid]['search_analytics_data'])) {
        if (is_array($data) && is_object($search_console_data[$nid]['search_analytics_data'])) {
          $analytics_data[$nid]['search_analytics_data'] = $search_console_data[$nid]['search_analytics_data'];
        }
      }
    }
    return $analytics_data;
  }

  /**
   * Set default value for the search console fields.
   *
   * @param \Drupal\google_kpis\Entity\GoogleKpis $google_kpi
   *   The google kpi entity.
   *
   * @return \Drupal\google_kpis\Entity\GoogleKpis
   *   The google kpi entity with default values.
   */
  public function setDefaultsSearchData(GoogleKpis $google_kpi) {
    $google_kpi->set('field_clicks', 0);
    $google_kpi->set('field_impressions', 0);
    $google_kpi->set('field_ctr', 0);
    $google_kpi->set('field_position', 99999);
    return $google_kpi;
  }

  /**
   * Set default value for the analytics fields.
   *
   * @param \Drupal\google_kpis\Entity\GoogleKpis $google_kpi
   *   The google kpi entity.
   *
   * @return \Drupal\google_kpis\Entity\GoogleKpis
   *   The google kpi entity with default values.
   */
  public function setDefaultsAnalyticsData(GoogleKpis $google_kpi) {
    $google_kpi->set('field_sessions_yesterday', '0');
    $google_kpi->set('field_users_yesterday', '0');
    $google_kpi->set('field_page_views_yesterday', '0');
    $google_kpi->set('field_og_searches_yesterday', '0');
    if (is_null($google_kpi->field_sessions_summary->value)) {
      $google_kpi->set('field_sessions_summary', '0');
    }
    if (is_null($google_kpi->field_users_summary->value)) {
      $google_kpi->set('field_users_summary', '0');
    }
    if (is_null($google_kpi->field_page_views_summary->value)) {
      $google_kpi->set('field_page_views_summary', '0');
    }
    if (is_null($google_kpi->field_og_searches_summary->value)) {
      $google_kpi->set('field_og_searches_summary', '0');
    }
    return $google_kpi;
  }

  /**
   * Set value as keys.
   *
   * @param array $array
   *   Input array.
   *
   * @return array
   *   Returns array with value as keys.
   */
  public function arraySetValueAsKey(array $array) {
    $new_array = [];
    foreach ($array as $value) {
      $new_array[$value] = $value;
    }
    return $new_array;
  }

}
