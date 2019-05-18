<?php

namespace Drupal\google_analytics_counter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Class GoogleAnalyticsCounterAppManager.
 *
 * @package Drupal\google_analytics_counter
 */
class GoogleAnalyticsCounterAppManager implements GoogleAnalyticsCounterAppManagerInterface {

  use StringTranslationTrait;

  /**
   * The table for the node__field_google_analytics_counter storage.
   */
  const TABLE = 'node__field_google_analytics_counter';

  /**
   * The google_analytics_counter.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The state where all the tokens are saved.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The language manager to get all languages for to get all aliases.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Drupal\google_analytics_counter\GoogleAnalyticsCounterAuthManagerInterface.
   *
   * @var \Drupal\google_analytics_counter\GoogleAnalyticsCounterAuthManagerInterface
   */
  protected $authManager;


  /**
   * Constructs a Google Analytics Counter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state of the drupal site.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager to find aliased resources.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\google_analytics_counter\GoogleAnalyticsCounterAuthManagerInterface $auth_manager
   *   Google Analytics Counter Auth Manager object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory, Connection $connection, StateInterface $state, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, LanguageManagerInterface $language, LoggerInterface $logger, MessengerInterface $messenger, GoogleAnalyticsCounterAuthManagerInterface $auth_manager) {
    $this->config = $config_factory->get('google_analytics_counter.settings');
    $this->connection = $connection;
    $this->state = $state;
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->languageManager = $language;
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->authManager = $auth_manager;
  }

  /**
   * Request report data.
   *
   * @param array $parameters
   *   An associative array containing:
   *   - profile_id: required [default='ga:profile_id']
   *   - dimensions: optional [ga:pagePath]
   *   - metrics: required [ga:pageviews]
   *   - sort: optional [ga:pageviews]
   *   - start-date: [default=-1 week]
   *   - end_date: optional [default=today]
   *   - start_index: [default=1]
   *   - max_results: optional [default=10,000].
   *   - filters: optional [default=none]
   *   - segment: optional [default=none]
   * @param array $cache_options
   *   An optional associative array containing:
   *   - cid: optional [default=md5 hash]
   *   - expire: optional [default=CACHE_TEMPORARY]
   *   - refresh: optional [default=FALSE].
   *
   * @return \Drupal\google_analytics_counter\GoogleAnalyticsCounterFeed|object
   *   A new GoogleAnalyticsCounterFeed object
   */
  public function reportData($parameters = [], $cache_options = []) {
    $config = $this->config;

    $step = $this->state->get('google_analytics_counter.data_step');
    $chunk = $config->get('general_settings.chunk_to_fetch');

    // Initialize the pointer.
    $pointer = $step * $chunk + 1;

    $parameters = [
      'profile_id' => 'ga:' . $config->get('general_settings.profile_id'),
      'dimensions' => ['ga:pagePath'],
      'metrics' => ['ga:pageviews'],
      'sort_metric' => NULL,
      'filters' => NULL,
      'segment' => NULL,
      'start_date' => !empty($config->get('general_settings.start_date')) ? strtotime($config->get('general_settings.start_date')) : strtotime($config->get('general_settings.custom_start_date')),
      'end_date' => !empty($config->get('general_settings.end_date')) ? strtotime($config->get('general_settings.end_date')) : strtotime($config->get('general_settings.custom_end_date')),
      'start_index' => $pointer,
      'max_results' => $chunk,
    ];

    $cache_options = [
      'cid' => 'google_analytics_counter_' . md5(serialize($parameters)),
      'expire' => GoogleAnalyticsCounterHelper::cacheTime(),
      'refresh' => FALSE,
    ];

    //Instantiate a new GoogleAnalyticsCounterFeed object.
    $feed = $this->gacGetFeed($parameters, $cache_options);

    // Set the total number of pagePaths for this profile from start_date to end_date.
    $this->state->set('google_analytics_counter.total_paths', $feed->results->totalResults);

    // The last time the Data was refreshed by Google. Not always available from Google.
    if (!empty($feed->results->dataLastRefreshed)) {
      $this->state->set('google_analytics_counter.data_last_refreshed', $feed->results->dataLastRefreshed);
    }

    // The first selfLink query to Google. Helpful for debugging in the dashboard.
    $this->state->set('google_analytics_counter.most_recent_query', $feed->results->selfLink);

    // The total number of pageViews for this profile from start_date to end_date.
    $this->state->set('google_analytics_counter.total_pageviews', $feed->results->totalsForAllResults['pageviews']);

    // The total number of pagePaths for this profile from start_date to end_date.
    $this->state->set('google_analytics_counter.total_paths', $feed->results->totalResults);

    // The number of results from Google Analytics in one request.
    $chunk = $config->get('general_settings.chunk_to_fetch');

    // Do one chunk at a time and register the data step.
    $step = $this->state->get('google_analytics_counter.data_step');

    // Which node to look for first. Must be between 1 - infinity.
    $pointer = $step * $chunk + 1;

    // Set the pointer equal to the pointer plus the chunk.
    $pointer += $chunk;

    $t_args = [
      '@size_of' => sizeof($feed->results->rows),
      '@first' => ($pointer - $chunk),
      '@second' => ($pointer - $chunk - 1 + sizeof($feed->results->rows)),
    ];
    $this->logger->info('Retrieved @size_of items from Google Analytics data for paths @first - @second.', $t_args);

    // Increase the step or set the step to 0 depending on whether
    // the pointer is less than or equal to the total results.
    if ($pointer <= $feed->results->totalResults) {
      $new_step = $step + 1;
    }
    else {
      $new_step = 0;
    }

    $this->state->set('google_analytics_counter.data_step', $new_step);

    return $feed;
  }

  /**
   * Update the path counts.
   *
   * @param int $index
   *   The index of the chunk to fetch and update.
   *
   * This function is triggered by hook_cron().
   *
   * @throws \Exception
   */
  public function gacUpdatePathCounts($index = 0) {
    $feed = $this->reportData($index);

    foreach ($feed->results->rows as $value) {
      // Use only the first 2047 characters of the pagepath. This is extremely long
      // but Google does store everything and bots can make URIs that exceed that length.
      $page_path = Html::escape($value['pagePath']);
      $string = (strlen($value['pagePath']) > 2047) ? substr($value['pagePath'],0,2047) : $value['pagePath'];

      // Update the Google Analytics Counter.
      $this->connection->merge('google_analytics_counter')
        ->key('pagepath_hash', md5($page_path))
        ->fields([
          'pagepath' => $string,
          'pageviews' => $value['pageviews'],
        ])
        ->execute();
    }

    // Log the results.
    $this->logger->info($this->t('Merged @count paths from Google Analytics into the database.', ['@count' => count($feed->results->rows)]));
  }

  /**
   * Save the pageview count for a given node.
   *
   * @param integer $nid
   *   The node id.
   * @param string $bundle
   *   The content type of the node.
   * @param int $vid
   *   Revision id value.
   *
   * @throws \Exception
   */
  public function gacUpdateStorage($nid, $bundle, $vid) {
    // Get all the aliases for a given node id.
    $aliases = [];
    $path = '/node/' . $nid;
    $aliases[] = $path;
    foreach ($this->languageManager->getLanguages() as $language) {
      $alias = $this->aliasManager->getAliasByPath($path, $language->getId());
      $aliases[] = $alias;
      if ($language->getId()) {
        $aliases[] = '/' . $language->getId() . $path;
        $aliases[] = '/' . $language->getId() . $alias;
      }
    }

    // Add also all versions with a trailing slash.
    $aliases = array_merge($aliases, array_map(function ($path) {
      return $path . '/';
    }, $aliases));

    // It's the front page
    if ($this->pathMatcher->isFrontPage()) {
      $sum_pageviews = $this->sumPageviews(['/']);
      $this->updateCounterStorage($nid, $sum_pageviews, $bundle, $vid);
    }
    else {
      $sum_pageviews = $this->sumPageviews(array_unique($aliases));
      $this->updateCounterStorage($nid, $sum_pageviews, $bundle, $vid);
    }
  }

  /**
   * Look up the count via the hash of the paths.
   *
   * @param $aliases
   * @return string
   *   Count of views.
   */
  protected function sumPageviews($aliases) {
    // $aliases can make pageview_total greater than pageviews
    // because $aliases can include page aliases, node/id, and node/id/, translations
    // redirects and other URIs which are all the same node.
    $hashes = array_map('md5', $aliases);
    $path_counts = $this->connection->select('google_analytics_counter', 'gac')
      ->fields('gac', ['pageviews'])
      ->condition('pagepath_hash', $hashes, 'IN')
      ->execute();
    $sum_pageviews = 0;
    foreach ($path_counts as $path_count) {
      $sum_pageviews += $path_count->pageviews;
    }

    return $sum_pageviews;
  }

  /**
   * Merge the sum of pageviews into google_analytics_counter_storage.
   *
   * @param int $nid
   *   Node id value.
   * @param int $sum_pageviews
   *   Count of page views.
   * @param string $bundle
   *   The content type of the node.
   * @param int $vid
   *   Revision id value.
   *
   * @throws \Exception
   */
  protected function updateCounterStorage($nid, $sum_pageviews, $bundle, $vid) {
    $this->connection->merge('google_analytics_counter_storage')
      ->key('nid', $nid)
      ->fields([
        'pageview_total' => $sum_pageviews,
      ])
      ->execute();

    // Update the Google Analytics Counter field if it exists.
    if (!$this->connection->schema()->tableExists(static::TABLE)) {
      return;
    }

    // Todo: This can be more performant by adding only the bundles that have been selected.
    $this->connection->upsert('node__field_google_analytics_counter')
      ->key('revision_id')
      ->fields(['bundle', 'deleted', 'entity_id', 'revision_id', 'langcode', 'delta', 'field_google_analytics_counter_value'])
      ->values([
        'bundle' => $bundle,
        'deleted' => 0,
        'entity_id' => $nid,
        'revision_id' => $vid,
        'langcode' => \Drupal::languageManager()->getDefaultLanguage()->getId(),
        'delta' => 0,
        'field_google_analytics_counter_value' => $sum_pageviews,
      ])
      ->execute();
  }

  /**
   * Instantiate a new GoogleAnalyticsCounterFeed object and query Google.
   *
   * @param array $parameters
   * @param array $cache_options
   *
   * @return object
   */
  protected function gacGetFeed(array $parameters, array $cache_options) {
    //Instantiate a new GoogleAnalyticsCounterFeed object.
    $feed = $this->authManager->newGaFeed();
    if (!$feed) {
      throw new \RuntimeException($this->t('The GoogleAnalyticsCounterFeed could not be initialized. Is Google Analytics Counter authenticated?'));
    }

    // Make the query to Google.
    $feed->queryReportFeed($parameters, $cache_options);

    // Handle errors.
    if (!empty($feed->error)) {
      throw new \RuntimeException($feed->error);
    }

    // If NULL then there is no error.
    if (!empty($feed->error)) {
      $t_arg = [
        '@error' => $feed->error,
      ];
      $this->logger->error('Google Analytics returned an error: [@error].', $t_arg);
    }
    return $feed;
  }

  /****************************************************************************/
  // Display gac count for $profile_id in the block and the filter.
  /****************************************************************************/

  /**
   * Get the count of pageviews for a path.
   *
   * @return string
   *   Count of page views.
   */
  public function gacDisplayCount() {
    // Make sure the path starts with a slash.
    $path = \Drupal::service('path.current')->getPath();
    $path = '/' . trim($path, ' /');

    $sum_pageviews = 0;
    // It's the front page.
    if ($this->pathMatcher->isFrontPage()) {
      $aliases = ['/'];
      $sum_pageviews = $this->sumPageviews($aliases);
    }

    // It's a node.
    else if ($node = \Drupal::routeMatch()->getParameter('node')) {
      if ($node instanceof \Drupal\node\NodeInterface) {
        $query = $this->connection->select('google_analytics_counter_storage', 'gacs');
        $query->fields('gacs', ['pageview_total']);
        $query->condition('nid', $node->id());
        $sum_pageviews = $query->execute()->fetchField();
      }
    }

    //It's a path.
    else {
      // Look up the alias, with, and without trailing slash.
      // todo: The array is an accommodation to sumPageViews()
      $aliases = [$this->aliasManager->getAliasByPath($path)];
      // dsm($aliases, '$aliases');
      $sum_pageviews = $this->sumPageviews($aliases);
      // dsm($sum_pageviews, '$sum_pageviews');

    }

    return number_format($sum_pageviews);
  }

}
