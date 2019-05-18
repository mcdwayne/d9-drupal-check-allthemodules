<?php

namespace Drupal\easy_google_analytics_counter;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
// --------- Use from google analytics library ----------.
use Google_Client;
use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_OrderBy;

/**
 * Class ConnectionService.
 */
class ConnectionService implements ConnectionServiceInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\File\FileSystemInterface definition.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Drupal\Core\Path\AliasManagerInterface definition.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface definition.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Google_Service_AnalyticsReporting definition.
   *
   * @var \Google_Service_AnalyticsReporting
   */
  protected $analytics;

  /**
   * Constructs a new ConnectionService object.
   */
  public function __construct(Connection $database,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    FileSystemInterface $file_system,
    AliasManagerInterface $alias_manager,
    LoggerChannelFactoryInterface $logger) {
    $this->database = $database;
    $this->config = $config_factory->get('easy_google_analytics_counter.admin');
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->aliasManager = $alias_manager;
    $this->logger = $logger;
  }

  /**
   * Initialize the google analytics service.
   */
  protected function initializeAnalytics() {
    // Use the developers console and download your service account
    // credentials in JSON format. Place them in this directory or
    // change the key file location if necessary.
    // $key_file_location = __DIR__ . '/service-account-credentials.json';
    // Create and configure a new client object.
    $client = new Google_Client();
    $client->setApplicationName($this->config->get('application_name'));
    $client->setAuthConfig($this->getKeyLocation());
    $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);

    $this->analytics = new Google_Service_AnalyticsReporting($client);
  }

  /**
   * {@inheritdoc}
   */
  public function request() {
    try {
      $this->initializeAnalytics();
    }
    catch (\Throwable $e) {
      $this->logger
        ->get('easy_google_analytics_counter')
        ->error('Process update page views from google analytics stopped. ' . $e->getMessage());
      return;
    }

    // Your view ID, defined on admin form.
    $view_id = $this->config->get('view_id');

    // Prepare google dimensions.
    $dimensions = ['ga:pagePath'];
    $dimension = $this->config->get('sort_dimension');
    if ($dimension !== 'ga:pageviews') {
      $dimensions[] = $dimension;
    }
    $google_dimensions = [];
    foreach ($dimensions as $dimension) {
      // Create the Dimensions object.
      $google_dimension = new Google_Service_AnalyticsReporting_Dimension();
      $google_dimension->setName($dimension);
      $google_dimensions[] = $google_dimension;
    }

    // Create the DateRange object.
    $days = $this->config->get('start_date');
    $start = strtotime("-$days day");
    $dateRange = new Google_Service_AnalyticsReporting_DateRange();
    $dateRange->setStartDate(date('Y-m-d', $start));
    $dateRange->setEndDate("today");

    // Create the Metrics object for Page Views.
    $pageViews = new Google_Service_AnalyticsReporting_Metric();
    $pageViews->setExpression('ga:pageviews');
    $pageViews->setAlias('pageviews');

    // Create the OrderBy object for Page Views descening.
    $sort = $this->config->get('sort_dimension') ? $this->config->get('sort_dimension') : 'ga:pageviews';
    $mode = $this->config->get('sort_mode') ? $this->config->get('sort_mode') : 'DESCENDING';
    $orderBy = new Google_Service_AnalyticsReporting_OrderBy();
    $orderBy->setFieldName($sort);
    $orderBy->setSortOrder($mode);

    // Create the ReportRequest object.
    $request = new Google_Service_AnalyticsReporting_ReportRequest();
    $request->setViewId($view_id);
    $request->setDateRanges($dateRange);
    $request->setMetrics([$pageViews]);
    $request->setDimensions($google_dimensions);
    $request->setOrderBys($orderBy);
    $request->setPageSize($this->config->get('number_items'));

    // Allow modules to request altering.
    \Drupal::moduleHandler()->alter('easy_google_analytics_counter_request', $request);

    $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
    $body->setReportRequests([$request]);

    // Allow modules to body altering.
    \Drupal::moduleHandler()->alter('easy_google_analytics_counter_body', $body);

    try {
      $response = $this->analytics->reports->batchGet($body);
      $this->setData($response);
    }
    catch (\Throwable $e) {
      $this->logger
        ->get('easy_google_analytics_counter')
        ->error($e->getMessage());
    }

  }

  /**
   * Set pageviews data to database.
   *
   * @param mixed $reports
   *   The response from GA.
   */
  protected function setData($reports) {
    $ga_views = [];
    for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
      $report = $reports[$reportIndex];
      $header = $report->getColumnHeader();
      $dimensionHeaders = $header->getDimensions();
      $rows = $report->getData()->getRows();
      $matches = [];
      for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
        $row = $rows[$rowIndex];
        $dimensions = $row->getDimensions();
        $metrics = $row->getMetrics();
        for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
          if ($dimensionHeaders[$i] === 'ga:pagePath') {
            $alias = $this->aliasManager->getPathByAlias(strstr($dimensions[$i], '?', 1));
            if (empty($alias)) {
              $alias = $this->aliasManager->getPathByAlias($dimensions[$i]);
            }
            if (preg_match('/node\/(\d+)(?<!\/)$/', $alias, $matches)) {
              $page_views = $metrics[0]->getValues();
              if (!empty($matches[1]) && !empty($page_views[0])) {
                if (!isset($ga_views[$matches[1]])) {
                  $ga_views[$matches[1]] = $page_views[0];
                }
                else {
                  $ga_views[$matches[1]] += $page_views[0];
                }
              }
            }
          }
        }
      }
    }

    // Write results to database;
    $this->updateNodePageViews($ga_views);

    // Results logging.
    $context = [
      '%count' => count($ga_views),
      '%elements' =>  http_build_query($ga_views, ' ', ';'),
    ];
    $this->logger
        ->get('easy_google_analytics_counter')
        ->notice('updated %count elements page views from Google Analytics: %elements', $context);
  }

  /**
   * Write results to database.
   *
   * @param array $ga_views
   *   The node page views array.
   */
  protected function updateNodePageViews(array $ga_views) {
    foreach ($ga_views as $nid => $value) {
      $this->database->update('node_field_data')
        ->fields(['page_views' => $value])
        ->condition('nid', $nid)
        ->condition(db_or()
          ->condition('page_views', $value, '>')
          ->isNull('page_views')
        )
        ->execute();
    }
  }

  /**
   * Return key file location.
   *
   * @return string
   *   The url of key file.
   */
  protected function getKeyLocation() {
    $url = '';
    $files = $this->config->get('service_account_credentials_json');
    if (!empty($files[0])) {
      if ($file = $this->entityTypeManager->getStorage('file')->load($files[0])) {
        $url = $this->fileSystem->realpath($file->uri->value);
      }
    }

    return $url;
  }

}
