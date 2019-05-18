<?php

namespace Drupal\google_appliance\Service;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\google_appliance\SearchResults\ResultSet;
use Drupal\google_appliance\SearchResults\SearchQuery;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Defines a search connector to GSA.
 */
class Search implements SearchInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Parser.
   *
   * @var \Drupal\google_appliance\Service\ParserInterface
   */
  protected $parser;

  /**
   * Constructs a new Search object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   * @param \Drupal\google_appliance\Service\ParserInterface $parser
   *   Parser.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler, ClientInterface $httpClient, ParserInterface $parser) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
    $this->httpClient = $httpClient;
    $this->parser = $parser;
  }

  /**
   * Performs search.
   *
   * @param \Drupal\google_appliance\SearchResults\SearchQuery $query
   *   Search query.
   *
   * @return \Drupal\google_appliance\SearchResults\ResultSet
   *   Search result set.
   */
  public function search(SearchQuery $query) {
    $config = $this->configFactory->get('google_appliance.settings');
    $resultsPerPage = (int) $config->get('display_settings.results_per_page');
    $params = [
      'site' => Html::escape($config->get('connection_info.collection')),
      'oe' => 'utf8',
      'ie' => 'utf8',
      'getfields' => '*',
      'client' => Html::escape($config->get('connection_info.frontend')),
      'start' => $query->getPage() * $resultsPerPage,
      'num' => Html::escape($config->get('display_settings.results_per_page')),
      'filter' => Html::escape($config->get('query_param.autofilter')),
      'q' => $query->getSearchQuery(),
      'output' => 'xml_no_dtd',
      'sort' => $query->getSort(),
      'access' => 'p',
    ];
    $this->moduleHandler->alter('google_appliance_query', $params);
    try {
      $response = $this->httpClient->request('GET', $config->get('connection_info.hostname') . '/search', [
        'query' => $params,
      ]);
      $return = $this->parser->parseResponse((string) $response->getBody());
    }
    catch (GuzzleException $e) {
      $return = (new ResultSet())->addError($e->getMessage(), ResultSet::ERROR_HTTP);
    }
    $this->moduleHandler->alter('google_appliance_response', $return);
    return $return
      ->setSearchTitle($config->get('display_settings.search_title'))
      ->setQuery($query)
      ->setResultsPerPage($resultsPerPage);
  }

}

// @todo: Finish these.

/**
 * Get related search via the Google Search Appliance clustering service.
 *
 * @return
 *   themed list of links
 */
function google_appliance_get_clusters() {

  // Grab module settings.
  $settings = _google_appliance_get_settings();

  // Get the search query.
  $query_pos = substr_count($settings['drupal_path'], '/') + 1;
  $search_query = urldecode(arg($query_pos));
  $cluster_content = NULL;

  // Perform POST to acquire the clusters  block.
  $clusterQueryURL = Html::escape($settings['hostname'] . '/cluster');
  $clusterQueryParams = [
    'q' => Html::escape($search_query),
    'btnG' => 'Google+Search',
    'access' => 'p',
    'entqr' => '0',
    'ud' => '1',
    'sort' => 'date:D:L:d1',
    'output' => 'xml_no_dtd',
    'oe' => 'utf8',
    'ie' => 'utf8',
    'site' => Html::escape($settings['collection']),
    'client' => Html::escape($settings['frontend']),
  ];

  // Alter request according to language filter settings.
  if (\Drupal::moduleHandler()
    ->moduleExists('locale') && $settings['language_filter_toggle']
  ) {
    $clusterQueryParams['lr'] = _google_appliance_get_lr($settings['language_filter_options']);
  }

  // cURL request for the clusters produces JSON result.
  $gsa_clusters_json = _curl_post($clusterQueryURL, $clusterQueryParams);

  // No error -> get the clusters.
  if (!$gsa_clusters_json['is_error']) {

    $clusters = json_decode($gsa_clusters_json['response'], TRUE);

    if (isset($clusters['clusters'][0])) {

      // Build the link list.
      $cluster_list_items = [];
      foreach ($clusters['clusters'][0]['clusters'] as $cluster) {
        // @FIXME
        // l() expects a Url object, created from a route name or external URI.
        // array_push($cluster_list_items, l($cluster['label'], $settings['drupal_path'] . '/' . $cluster['label']));
      }

      // Create theme-friendly list of links render array.
      $cluster_list = [
        '#theme' => 'item_list',
        '#items' => $cluster_list_items,
        '#title' => NULL,
        '#type' => 'ul',
        '#attributes' => [],
      ];

      // Allow implementation of hook_google_appliance_cluster_list_alter() by
      // other modules.
      \Drupal::moduleHandler()
        ->alter('google_appliance_cluster_list', $cluster_list, $cluster_results);

      $cluster_content = \Drupal::service('renderer')->render($cluster_list);
    }
  }

  return $cluster_content;
}

/**
 * Report search errors to the log.
 */
function _google_appliance_log_search_error($search_keys = NULL, $error_string = NULL) {
  $settings = _google_appliance_get_settings();

  // Build log entry.
  $type = 'google_appliance';
  $message = 'Search for %keys produced error: %error_string';
  $vars = [
    '%keys' => isset($search_keys) ? $search_keys : 'nothing (empty search submit)',
    '%error_string' => isset($error_string) ? $error_string : 'undefinded error',
  ];
  // @FIXME
  // l() expects a Url object, created from a route name or external URI.
  // $link = l(t('view reproduction'), $settings['drupal_path'] . '/' . \Drupal\Component\Utility\Html::escape($search_keys));
  \Drupal::logger($type)->notice($message, []);
}

/**
 * Get googleon/googleoff tags.
 */
function _google_appliance_get_googleonoff() {
  return [
    'index' => [
      'prefix' => '<!--googleoff: index-->',
      'suffix' => '<!--googleon: index-->',
    ],
    'anchor' => [
      'prefix' => '<!--googleoff: anchor-->',
      'suffix' => '<!--googleon: anchor-->',
    ],
    'snippet' => [
      'prefix' => '<!--googleoff: snippet-->',
      'suffix' => '<!--googleon: snippet-->',
    ],
    'all' => [
      'prefix' => '<!--googleoff: all-->',
      'suffix' => '<!--googleon: all-->',
    ],
  ];
}

/**
 * Format the value of the 'lr' parameter appropriately.
 *
 * @param $options
 *   Array of languages to filter, as defined by the config
 *   variable google_appliance_language_filter_options.
 *
 * @return
 *   String to be passed to the GSA using the 'lr' parameter.
 */
function _google_appliance_get_lr($options) {
  $langcodes = [];
  $options = array_filter($options);
  foreach ($options as $option) {
    switch ($option) {
      case '***CURRENT_LANGUAGE***':
        $language = \Drupal::languageManager()->getCurrentLanguage();
        $langcode = $language->language;
        break;

      case '***DEFAULT_LANGUAGE***':
        $langcode = language_default('language');
        break;

      default:
        $langcode = $option;
    }
    $langcodes[$langcode] = "lang_$langcode";
  }
  return implode('|', $langcodes);
}
