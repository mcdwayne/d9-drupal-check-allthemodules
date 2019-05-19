<?php

namespace Drupal\sitesearch_360\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\key\KeyRepository;
use GuzzleHttp\Client;

/**
 * Handles search using Site Search 360 service.
 *
 * @SearchPlugin(
 *   id = "site_search_360",
 *   title = @Translation("Site Search 360")
 * )
 */
class SiteSearch360 extends ConfigurableSearchPluginBase implements AccessibleInterface {

  /**
   * Total number of results.
   *
   * @var int
   */
  protected $totalResults;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Key storage.
   *
   * @var \Drupal\key\KeyRepository
   */
  protected $keyRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('key.repository')
    );
  }

  /**
   * Constructs a \Drupal\node\Plugin\Search\NodeSearch object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\key\KeyRepository $key_repository
   *   The key repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, KeyRepository $key_repository) {
    $this->httpClient = $http_client;
    $this->keyRepository = $key_repository;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $values["api_base_url"] = 'https://api.sitesearch360.com/sites';
    $values["api_key"] = NULL;
    $values["site_id"] = NULL;
    $values["page_size"] = 20;
    $values["highlight_query_terms"] = TRUE;
    $values["enable_suggests"] = TRUE;
    $values["suggests_size"] = 5;
    $values["suggests_min_chars"] = 3;
    $values["suggests_forms"] = '#search-form, #search-block-form';
    $values["logging"] = TRUE;

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // If the API key is available,
    // we retrieve and display the Index Status info.
    if ($this->configuration['api_key']) {

      $status = $this->getIndexStatus();

      $form['index_status'] = [
        '#title' => $this->t('Index status'),
        '#type' => 'details',
        '#open' => FALSE,
      ];

      $form['index_status']['details'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Indexed'),
          $this->t('Client error'),
          $this->t('Server error'),
          $this->t('Skipped'),
        ],
        '#rows' => [
          [
            $status['pages'],
            $status['4XX'],
            $status['5XX'],
            $status['8XX'],
          ],
        ],
      ];
    }

    $form['main'] = [
      '#title' => $this->t('Main'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['main']['api_base_url'] = [
      '#title' => $this->t('Search engine base url'),
      '#type' => 'textfield',
      '#description' => $this->t('The REST API base URL.'),
      '#default_value' => $this->configuration['api_base_url'],
    ];

    $form['main']['api_key'] = [
      '#title' => $this->t('Search engine API Key'),
      '#type' => 'key_select',
      '#description' => $this->t('Your Search engine unique API Key.'),
      '#default_value' => $this->configuration['api_key'],
    ];

    $form['main']['site_id'] = [
      '#title' => $this->t('Search engine Site ID'),
      '#type' => 'textfield',
      '#description' => $this->t('Your Search engine Site ID.'),
      '#default_value' => $this->configuration['site_id'],
    ];

    $form['standard_search'] = [
      '#title' => $this->t('Standard search'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['standard_search']['page_size'] = [
      '#title' => $this->t('Page size'),
      '#type' => 'textfield',
      '#description' => $this->t('Number of results to display per page.'),
      '#default_value' => $this->configuration['page_size'],
      '#size' => 5,
      '#max_length' => 5,
    ];

    $form['standard_search']['highlight_query_terms'] = [
      '#title' => $this->t('Highlight query terms'),
      '#type' => 'checkbox',
      '#description' => $this->t('If set to true, the query terms will be wrapped in order to get a specific styling. By default the parameter is true.'),
      '#default_value' => $this->configuration['highlight_query_terms'],
    ];

    $form['suggests_search'] = [
      '#title' => $this->t('Suggestions search'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['suggests_search']['enable_suggests'] = [
      '#title' => $this->t('Enable suggestions'),
      '#type' => 'checkbox',
      '#description' => $this->t('If set to true, an autocomplete widget will be activated for the block and page search fields.'),
      '#default_value' => $this->configuration['enable_suggests'],
    ];

    $form['suggests_search']['suggests_size'] = [
      '#title' => $this->t('Suggests size'),
      '#type' => 'textfield',
      '#description' => $this->t('Number of suggestions to fetch from the API'),
      '#default_value' => $this->configuration['suggests_size'],
      '#size' => 5,
      '#max_length' => 5,
    ];

    $form['suggests_search']['suggests_min_chars'] = [
      '#title' => $this->t('Suggests minimum characters'),
      '#type' => 'textfield',
      '#description' => $this->t('The number of character to type before displaying the suggestions'),
      '#default_value' => $this->configuration['suggests_min_chars'],
      '#size' => 5,
      '#max_length' => 5,
    ];

    $form['suggests_search']['suggests_forms'] = [
      '#title' => $this->t('Enabled forms'),
      '#type' => 'textfield',
      '#description' => $this->t('Comma separated list of jQuery selectors for Forms that have suggestions enabled'),
      '#default_value' => $this->configuration['suggests_forms']
    ];

    $form['misc'] = [
      '#title' => $this->t('Miscellaneous'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    $form['misc']['logging'] = [
      '#title' => $this->t('Log queries'),
      '#type' => 'checkbox',
      '#description' => $this->t('Disable this to prevent logging of the queries. Useful to avoid logs and stats skewing during testing.'),
      '#default_value' => $this->configuration['logging'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $keys = [
      'api_key',
      'api_base_url',
      'site_id',
      'page_size',
      'highlight_query_terms',
      'enable_suggests',
      'suggests_size',
      'suggests_min_chars',
      'suggests_forms',
      'logging',
    ];
    foreach ($keys as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if ($this->isSearchExecutable()) {

      $page = pager_find_page();
      $results = $this->getResults($page);

      pager_default_initialize($this->totalResults, $this->configuration['page_size']);

      if ($results) {

        // Allow other modules to alter the results before they are rendered.
        \Drupal::moduleHandler()->alter('sitesearch_360_results', $results);

        return $this->prepareResults($results);
      }
    }

    return [];
  }

  /**
   * Check if suggests autocomplete are enabled.
   *
   * @return bool
   *   The setting value.
   */
  public function enableSuggests() {
    return $this->configuration['enable_suggests'];
  }

  /**
   * Get query result.
   *
   * @param int $page
   *   The current page number.
   *
   * @return object|null
   *   Decoded response from Site Search 360, or NULL on error.
   */
  protected function getResults($page) {
    $params = [
      'query' => [
        'query' => $this->keywords,
        'limit' => $this->configuration['page_size'],
        'offset' => $page * $this->configuration['page_size'],
        'site' => $this->configuration['site_id'],
        'highlightQueryTerms' => $this->configuration['highlight_query_terms'] ? 'true' : 'false',
        'log' => $this->configuration['logging'] ? 'true' : 'false',
        'includeContent' => 'true',
      ],
    ];

    // Allow other modules to alter the query parameters.
    \Drupal::moduleHandler()->alter('sitesearch_360_search_query_params', $params);

    $response = $this->queryApi($this->configuration['api_base_url'], $params);

    $results = json_decode($response->getBody()->getContents(), TRUE);

    $this->totalResults = $results['totalResults'];

    return $results;
  }

  /**
   * Get query suggestions.
   *
   * @param string $query
   *   The search query.
   *
   * @return array
   *   The suggestions array.
   */
  public function getSuggests($query) {
    $params = [
      'query' => [
        'query' => $query,
        'limit' => $this->configuration['suggests_size'],
        'site' => $this->configuration['site_id'],
      ],
    ];

    // Allow other modules to alter the query parameters.
    \Drupal::moduleHandler()->alter('sitesearch_360_suggests_query_params', $params);

    $response = $this->queryApi($this->configuration['api_base_url'] . '/suggest', $params);

    $results = json_decode($response->getBody()->getContents(), TRUE);

    return $this->prepareSuggests($results);
  }

  /**
   * Run the query against the Site Search REST API.
   *
   * @param string $url
   *   The API url.
   * @param array $params
   *   The query parameters.
   *
   * @return null|\Psr\Http\Message\ResponseInterface
   *   The API response or null, if failed.
   */
  protected function queryApi($url, array $params) {
    try {
      return $this->httpClient->get($url, $params);
    }
    catch (\Exception $e) {
      \Drupal::logger('my_module')->error($e->getMessage());
      return NULL;
    }
  }

  /**
   * Prepare search results for rendering.
   *
   * @param array $results
   *   Results as returned by the API.
   *
   * @return array
   *   Array of search result items render arrays (empty array if no results).
   */
  protected function prepareResults(array $results) {
    $items = [];

    if (!isset($results['suggests']['_'])) {
      return $items;
    }

    foreach ($results['suggests']['_'] as $item) {
      $items[] = [
        'link' => $item['link'],
        'image' => $item['image'],
        'type' => NULL,
        'title' => $item['name'],
        'node' => NULL,
        'extra' => NULL,
        'score' => NULL,
        'snippet' => [
          '#markup' => $item['content'],
        ],
      ];
    }

    // Allow other modules to alter the results.
    \Drupal::moduleHandler()->alter('sitesearch_360_prepared_results', $items);

    return $items;
  }

  /**
   * Prepare search suggests.
   *
   * @param array $results
   *   Results as returned by the API.
   *
   * @return array
   *   Array of suggest items or empty array if no results.
   */
  protected function prepareSuggests(array $results) {
    $items = [];

    foreach ($results['suggests']['_'] as $item) {
      $items[] = [
        'label' => $item['name'],
        'value' => $item['link'],
      ];
    }

    // Allow other modules to alter the suggests.
    \Drupal::moduleHandler()->alter('sitesearch_360_prepared_suggests', $items);

    return $items;
  }

  /**
   * Retrieve the index status.
   *
   * @return array
   *   The index status data.
   */
  protected function getIndexStatus() {
    $apiKey = $this->keyRepository->getKey($this->configuration['api_key']);
    $params = [
      'query' => [
        'token' => $apiKey->getKeyValue(),
      ],
    ];

    $response = $this->queryApi($this->configuration['api_base_url'] . '/indexStatus', $params);

    return json_decode($response->getBody()->getContents(), TRUE);
  }

}
