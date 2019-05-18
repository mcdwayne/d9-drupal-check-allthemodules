<?php

namespace Drupal\funnelback;

/**
 * Service class for funnelback module.
 */
class Funnelback {

  /**
   * Maximum title length.
   *
   * @var int
   */
  protected $titleMaxLength = 80;

  /**
   * Funnelback syggestions path.
   *
   * @var string
   */
  protected $suggestPath = 's/suggest.json';

  /**
   * Funnelback API path.
   *
   * @var string
   */
  protected $apiPath = 's/search.json';

  /**
   * Funnelback collection.
   *
   * @var string
   */
  protected $collection;

  /**
   * Funnelback profile.
   *
   * @var string
   */
  protected $profile;

  /**
   * Number of items to show.
   *
   * @var int
   */
  protected $numberToShow;

  /**
   * Base URL.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Constructs a Funnelback object.
   *
   * @param string $collection
   *   Funnelback API path.
   * @param string $profile
   *   Funnelback profile.
   * @param int $number
   *   Number of items to show.
   * @param string $baseUrl
   *   Base URL.
   */
  public function __construct($collection, $profile, $number, $baseUrl) {
    $this->collection = $collection;
    $this->profile = $profile;
    $this->numberToShow = $number;
    $this->baseUrl = $baseUrl;
  }

  /**
   * Cache search results.
   *
   * Need to keep a cache of the search results for the entire page duration,
   * so blocks can access it.
   */
  public static function funnelbackStaticResultsCache($results = NULL) {
    static $staticResults = NULL;
    if (is_array($results)) {
      $staticResults = $results;
    }

    return $staticResults;
  }

  /**
   * Calls the appropriate Funnelback web service interface.
   *
   * @param string $query
   *   The query.
   * @param int $start
   *   A start value.
   * @param string $partialQuery
   *   Partial query for autocompletion.
   * @param array $facetQuery
   *   An array of facet queries.
   * @param array $contextualQuery
   *   An array of contextual queries.
   * @param Drupal\funnelback\FunnelbackClient $funnelbackClient
   *   FunnelbackClient object.
   * @param array $customTemplate
   *   Custom rendered form array.
   *
   * @return array|null
   *   An array of results when successful or NULL on failure.
   */
  public function funnelbackDoQuery($query, $start = 1, $partialQuery = NULL, array $facetQuery = NULL, array $contextualQuery = NULL, FunnelbackClient $funnelbackClient = NULL, array $customTemplate = NULL) {

    $baseUrl = $this->funnelbackGetBaseUrl();

    // Set API paths.
    $apiPath = $this->apiPath;

    $query = FunnelbackQueryString::funnelbackQueryNormaliser($query);

    $requestParams = [
      'remote_ip' => ip_address(),
      'query' => $query,
      'start_rank' => $start,
      'collection' => $this->collection,
      'profile' => $this->profile,
    ];

    // Add custom template.
    if ($customTemplate) {
      $requestParams['form'] = $customTemplate;
      $apiPath = 's/search.html';
    }

    // Add facet query to request.
    if (is_array($facetQuery)) {
      $requestParams = array_merge($requestParams, $facetQuery);
    }

    // Add contextual query to request.
    if (is_array($contextualQuery)) {
      $requestParams = array_merge($requestParams, $contextualQuery);
    }

    // Compose autocomplete request.
    if (isset($partialQuery)) {
      // It is from autocompletion request.
      $requestParams = [
        'remote_ip' => ip_address(),
        'partial_query' => $partialQuery,
        'collection' => $this->collection,
        'show' => $this->numberToShow,
        'fmt' => 'json++',
      ];
      // Set API paths.
      $apiPath = $this->suggestPath;
    }

    if (!empty($this->profile)) {
      $requestParams['profile'] = $this->profile;
    }

    // Allow modules to modify the query parameters.
    drupal_alter('funnelback_query', $requestParams);

    // Do the request.
    $response = $funnelbackClient->request($baseUrl, $apiPath, $requestParams);

    if ($response->code == 200) {
      $result = $this->funnelbackJsonQuery(drupal_json_decode($response->data), $baseUrl);
    }
    else {
      $funnelbackClient->debug('The search query failed due to "%error".', [
        '%error' => $response->code . ' ' . $response->error,
      ], WATCHDOG_WARNING);
      return FALSE;
    }

    // Allow modules to modify the query result.
    drupal_alter('funnelback_result', $result);

    return $result;
  }

  /**
   * Calls the Funnelback JSON web service interface.
   *
   * @param object $json
   *   A HTML response object.
   * @param string $baseUrl
   *   The base URL of this search.
   *
   * @return array|object
   *   An array containing results data.
   */
  public function funnelbackJsonQuery($json, $baseUrl) {

    if (!isset($json['response'])) {
      // This is the autocompletion response or custom template.
      $this->funnelbackStaticResultsCache([]);

      return $json;
    }

    $result = $json['response']['resultPacket'];

    if (!$result) {
      // Profile name not found.
      $this->funnelbackStaticResultsCache([]);

      return [];
    }

    // Load up the results summary.
    $summary = [
      'start' => (int) $result['resultsSummary']['currStart'],
      'end' => (int) $result['resultsSummary']['currEnd'],
      'page_size' => (int) $result['resultsSummary']['numRanks'],
      'total' => (int) $result['resultsSummary']['totalMatching'],
      'query' => (string) $result['query'],
      'base_url' => $baseUrl,
    ];

    $spell = [];
    if (!empty($result['spell'])) {
      $suggestion = [
        'url' => $result['spell']['url'],
        'text' => $result['spell']['text'],
      ];
      $spell[] = $suggestion;
    }

    $curator = $json['response']['curator'];

    $items = [];
    if (!empty($result)) {
      foreach ($result['results'] as $resultItem) {
        $title = $resultItem['title'];
        if (strlen($title) > $this->titleMaxLength) {
          $title = substr_replace($title, '&hellip;', $this->titleMaxLength);
        }
        $liveUrl = (string) $resultItem['liveUrl'];

        $item = [
          'title' => $title,
          'date' => (string) $resultItem['date'],
          'summary' => (string) $resultItem['summary'],
          'live_url' => $this->funnelbackTruncateUrl($liveUrl),
          'cache_url' => (string) $resultItem['cacheUrl'],
          'display_url' => $resultItem['displayUrl'],
          'metaData' => $resultItem['metaData'],
        ];
        if (isset($resultItem['metaData']['nodeId'])) {
          $item['metaData']['nodeId'] = $resultItem['metaData']['nodeId'];
        }
        else {
          $item['metaData']['nodeId'] = NULL;
        }

        $items[] = $item;
      }
    }

    // Load up the contextual navigation.
    $contextualNav = [];
    if (!empty($result['contextualNavigation']['categories'])) {
      foreach ($result['contextualNavigation']['categories'] as $category) {
        $navItem = [];
        $navItem['name'] = $category['name'];
        if (!empty($category['more_link'])) {
          $navItem['more_link'] = $category['more_link'];
        }

        $clusters = [];
        if (!empty($category['clusters'])) {
          foreach ($category['clusters'] as $cluster) {
            $clusters[] = [
              'title' => $cluster['label'],
              'count' => $cluster['count'],
              'link' => $cluster['href'],
            ];
          }
          $navItem['clusters'] = $clusters;
        }

        $contextualNav[] = $navItem;
      }
    }

    // Load up the facet content.
    $facets = [];
    if (!empty($json['response']['facets'])) {
      $facets = $json['response']['facets'];
    }

    // Return the results.
    $results = [
      'summary' => $summary,
      'spell' => $spell,
      'curator' => $curator,
      'results' => $items,
      'contextual_nav' => $contextualNav,
      'facets' => $facets,
      'facetExtras' => $json['response']['facetExtras'],
    ];

    $this->funnelbackStaticResultsCache($results);

    return $results;
  }

  /**
   * Return the base URL.
   *
   * @return string|null
   *   The base URL.
   */
  protected function funnelbackGetBaseUrl() {
    $baseUrl = rtrim($this->baseUrl, '/');
    return $baseUrl . '/';
  }

  /**
   * Check non web files being displayed as file types (not html, cfm, etc).
   *
   * @return bool
   *   True if type is one of accepted types.
   */
  protected function funnelbackCheckFiletype($type) {
    $acceptedTypes = ['pdf', 'xls', 'ppt', 'rtf', 'doc', 'docx'];
    return in_array($type, $acceptedTypes);
  }

  /**
   * Truncate the display url so it displays on one line.
   *
   * @param string $url
   *   URL to trancate.
   *
   * @return string
   *   Modified URL.
   */
  protected function funnelbackTruncateUrl($url) {
    // Split the url into bits so we can choose what to keep.
    $urlArr = parse_url($url);
    $host = $urlArr['host'];
    // Always keep the host.
    $maxLength = $this->titleMaxLength - strlen($host);
    $path = $urlArr['path'];
    $query = (!empty($urlArr['query'])) ? $urlArr['query'] : NULL;
    if (!empty($query)) {
      $path = $path . '?' . $query;
    }
    // Put elipsis in the middle of the path.
    $pathLength = strlen($path);
    if ($pathLength > $maxLength) {
      $start = $maxLength / 2;
      $trunc = $pathLength - $maxLength;
      $path = substr_replace($path, '&hellip;', $start, $trunc);
    }

    return $host . $path;
  }

  /**
   * Removed unsupported display formats from facets array.
   *
   * @param array $facets
   *   Array of facets.
   */
  public static function funnelbackFilterFacetDisplay(array &$facets) {
    $supportedFormat = [
      'SINGLE_DRILL_DOWN',
      'CHECKBOX',
      'RADIO_BUTTON',
    ];

    foreach ($facets as $key => $facet) {
      // Filter other display types out.
      if (!in_array($facet['guessedDisplayType'], $supportedFormat)) {
        unset($facets[$key]);
      }
    }
  }

  /**
   * Validate search result JSON.
   *
   * @param array $result
   *   JSON search results.
   *
   * @return bool
   *   False if result empty or does not include all default keys.
   */
  public static function funnelbackResultValidator(array $result) {
    if (!is_array($result)) {
      return FALSE;
    }
    $defaultResultKeys = [
      'results',
      'summary',
      'facets',
      'facetExtras',
      'spell',
      'curator',
    ];
    foreach ($defaultResultKeys as $key) {
      if (!in_array($key, array_keys($result))) {
        // Default key is not in results, custom template used.
        return FALSE;
      }
    }

    return TRUE;
  }

}
