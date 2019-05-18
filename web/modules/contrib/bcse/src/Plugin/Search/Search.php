<?php

namespace Drupal\bcse\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Drupal\key\KeyRepository;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Search\Plugin\SearchInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Handles search using Bing Custom Search.
 *
 * @SearchPlugin(
 *   id = "bcse_search",
 *   title = @Translation("Bing Custom Search")
 * )
 */
class Search extends ConfigurableSearchPluginBase implements AccessibleInterface {

  /**
   * Max number of items (`count`) via API.
   */
  const MAX_NUM = 50;

  /**
   * Total number of results.
   *
   * @var integer
   */
  protected $count;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

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
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('language_manager'),
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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\key\KeyRepository $key_repository
   *   The key repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, Client $http_client, KeyRepository $key_repository) {
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
    $this->keyRepository = $key_repository;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Allow overrides, e.g. different search engines per language.
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $all_languages = $this->languageManager->getLanguages();
    $values = [];
    foreach($all_languages as $language) {
      $values['market_' . $language->getId()] = NULL;
    }
    $values['primary_key'] = NULL;
    $values['secondary_key'] = NULL;
    $values['custom_config'] = NULL;
    $values['api_endpoint'] = 'https://api.cognitive.microsoft.com/bingcustomsearch/v5.0/search';
    $values['page_size'] = 10;
    $values['safe_search'] = 'Off';
    $values['text_decorations'] = FALSE;
    $values['text_format'] = 'HTML';

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['primary_key'] = [
      '#title' => $this->t('Primary Key'),
      '#type' => 'key_select',
      '#required' => TRUE,
      '#default_value' => $this->configuration['primary_key'],
    ];

    $form['secondary_key'] = [
      '#title' => $this->t('Secondary Key'),
      '#type' => 'key_select',
      '#required' => TRUE,
      '#default_value' => $this->configuration['secondary_key'],
    ];

    $form['custom_config'] = [
      '#title' => $this->t('Custom Configuration ID'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configuration['custom_config'],
    ];

    $all_languages = $this->languageManager->getLanguages();
    foreach($all_languages as $language) {
      $form['market_' . $language->getId() ] = [
        '#title' => $this->t('Market (' . $language->getName() . ')'),
        '#type' => 'textfield',
        '#default_value' => $this->configuration['market_' . $language->getId()],
        '#description' => $this->t('The market where the results come from. Typically the country where the user is making the request from. The market must be in the form <em>en-US</em>.'),
      ];
    }

    $form['api_endpoint'] = [
      '#title' => $this->t('API Endpoint'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['api_endpoint'],
      '#required' => TRUE,
    ];

    $form['page_size'] = [
      '#title' => $this->t('Page size'),
      '#type' => 'textfield',
      '#description' => $this->t('Number of results to display per page.'),
      '#default_value' => $this->configuration['page_size'],
      '#size' => 5,
      '#max_length' => 5,
    ];

    $form['safe_search'] = [
      '#title' => $this->t('Safe Search'),
      '#type' => 'select',
      '#options' => [
        'Off' => t('Off'),
        'Moderate' => t('Moderate'),
        'Strict' => t('Strict'),
      ],
      '#description' => $this->t('A filter used to filter webpages for adult content.'),
      '#default_value' => $this->configuration['safe_search'],
    ];

    $form['text_decorations'] = [
      '#title' => $this->t('Use text decorations'),
      '#type' => 'checkbox',
      '#description' => $this->t('Should snippets contain decoration markers such as hit highlighting characters.'),
      '#default_value' => $this->configuration['text_decorations'],
    ];

    $form['text_format'] = [
      '#title' => $this->t('Text format'),
      '#type' => 'select',
      '#options' => [
        'Raw' => $this->t('Raw'),
        'HTML' => $this->t('HTML'),
      ],
      '#description' => $this->t('Use Unicode characters or HTML tags to mark content that needs special formatting.'),
      '#default_value' => $this->configuration['text_format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $keys = [];
    $languages = $this->languageManager->getLanguages();
    foreach($languages as $language) {
      $keys[] = 'market_' . $language->getId();
    }
    $defaults = [
      'primary_key',
      'secondary_key',
      'custom_config',
      'api_endpoint',
      'page_size',
      'safe_search',
      'text_decorations',
      'text_format',
    ];
    $keys = array_merge($keys, $defaults);
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
      $results = $this->findResults($page);

      // API total results is unreliable. Sometimes when requesting a large
      // offset we get no results, and
      // $response->searchInformation->totalResults is 0. In this case return
      // the previous page's items.
      while ($page && !count($results)) {
        $results = $this->findResults(--$page);
      }

      pager_default_initialize($this->count, $this->configuration['page_size']);

      if ($results) {
        return $this->prepareResults($results);
      }
    }

    return array();
  }

  /**
   * Queries to find search results, and sets status messages.
   *
   * This method can assume that $this->isSearchExecutable() has already been
   * checked and returned TRUE.
   *
   * @return array|null
   *   Results from search query execute() method, or NULL if the search
   *   failed.
   */
  protected function findResults($page) {
    $items = [];

    $page_size = $this->configuration['page_size'];

    // Reconcile items per page with API max 50.
    $count = 0;
    $n = $page_size < self::MAX_NUM ? $page_size : self::MAX_NUM;
    for ($i = 0; $i < $page_size; $i += self::MAX_NUM) {
      $offset = $page * $page_size + $i;
      if (!$response = $this->getResults($n, $offset)) {
        break;
      }
      if (isset($response->webPages->value)) {
        $this->count = $response->webPages->totalEstimatedMatches;
        $items = array_merge($items, $response->webPages->value);
      }
      else {
        break;
      }
    }

    return $items;
  }

  /**
   * Get query result.
   *
   * @param int $n
   *   Number of items.
   * @param int $offset
   *   Offset of items (0-indexed).
   *
   * @return object|null
   *   Decoded response from Bing, or NULL on error.
   */
  protected function getResults($n = 1, $offset = 0) {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $primary_key = $this->keyRepository->getKey($this->configuration['primary_key']);
    $market = $this->configuration['market_' . $language];

    // Make sure we actually have a API enndpoint.
    $api_endpoint = $this->configuration['api_endpoint'];
    if (empty($api_endpoint)) {
      $default_config = $this->defaultConfiguration();
      $api_endpoint = $default_config['api_endpoint'];
    }

    $keywords = $this->getKeywords();

    $options = [
      'query' => [
        'q' => $keywords,
        'customconfig' => $this->configuration['custom_config'],
        'responseFilter' => 'Webpages',
        'mkt' => $market,
        'safesearch' => $this->configuration['safe_search'],
        'offset' => $offset,
        'count' => $n,
        'textDecorations' => $this->configuration['text_decorations'] ? 'true' : 'false',
        'textFormat' => $this->configuration['text_format'],
      ],
      'headers' => [
        'Ocp-Apim-Subscription-Key' => $primary_key->getKeyValue(),
      ],
    ];

    try {
      $response = $this->httpClient->get($api_endpoint, $options);
    } catch (\Exception $e) {
      \Drupal::logger('bcse')->error($e->getMessage());
      return NULL;
    }

    return json_decode($response->getBody());
  }

  /**
   * Prepares search results for rendering.
   *
   * @param array $items
   *   Results found from a successful search query execute() method.
   *
   * @return array
   *   Array of search result item render arrays (empty array if no results).
   */
  protected function prepareResults(array $items) {
    $results = [];
    foreach ($items as $item) {
      // Available keys: [id, name, url, displayUrl, snippet].
      $results[] = [
        'link' => $item->url,
        'type' => NULL,
        'title' => $item->name,
        'node' => NULL,
        'extra' => NULL,
        'score' => NULL,
        'img_src' => NULL,
        'file_format' => NULL,
        'snippet' => [
          '#markup' => $item->snippet,
        ],
        'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      ];
    }
    return $results;
  }

  /**
   * Gets render array for search option links.
   */
  public function getSearchOptions(Request $request) {
    $options = [];

    if (count($options)) {
      $query = $this->getParameters();
      $active = empty($query['type']);
      if (!$active) {
        unset($query['type']);
      }
      $url = Url::createFromRequest($request);
      $url->setOption('query', $query);
      $url->setOption('attributes', $active ? ['class' => ['is-active']] : []);
      $options['all'] = [
        '#title' => $this->t('All'),
        '#type' => 'link',
        '#url' => $url,
        '#weight' => -1,
      ];

      return [
        '#theme' => 'item_list',
        '#items' => $options,
      ];
    }
    return [];
  }
}
