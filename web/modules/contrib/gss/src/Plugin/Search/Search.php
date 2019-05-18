<?php

namespace Drupal\gss\Plugin\Search;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Random;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
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
 * Handles search using Google Search Engine.
 *
 * @SearchPlugin(
 *   id = "gss_search",
 *   title = @Translation("Google Site Search")
 * )
 */
class Search extends ConfigurableSearchPluginBase implements AccessibleInterface {

  /**
   * Max number of items (`num`) via API.
   */
  const MAX_NUM = 10;

  /**
   * Total number of results.
   *
   * @var integer
   */
  protected $count;

  /**
   * Labels (facets) for the current search.
   *
   * @var object
   */
  protected $labels;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * A module manager object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
      $container->get('module_handler'),
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module manager object.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\key\KeyRepository $key_repository
   *   The key repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, Client $http_client, KeyRepository $key_repository) {
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
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
    $all_languages = $this->getSupportedLanguages();
    $values = [];
    foreach($all_languages as $language) {
      $values["search_engine_id_" . $language->getId()] = NULL;
    }
    $values["api_key"] = NULL;
    $values["base_url"] = 'https://www.googleapis.com/customsearch/v1';
      // @todo autocomplete
      // "autocomplete" => TRUE,
    $values["page_size"] = 10;
    $values["pager_size"] = 9;
    $values["images"] = FALSE;
    $values["labels"] = TRUE;
      // @todo number_of_results
      // "number_of_results" => TRUE,
      // @todo info
      // "info" => FALSE,

    return $values;
  }

  /**
   * Gets the configured pager size.
   */
  public function getPagerSize() {
    return $this->configuration['pager_size'];
  }

  /**
   * Gets the search results count.
   */
  public function getCount() {
    return $this->count;
  }

  /**
   * Get supported languages.
   *
   * @return array
   *   An array of supported language objects.
   */
  public function getSupportedLanguages() {
    $languages = [];

    // Special any language.
    $any_langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $any_language = $this->languageManager->getLanguage($any_langcode);
    if ($any_language) {
      $languages[$any_langcode] = $any_language;
    }

    // Configurable languages.
    $languages += $this->languageManager->getLanguages();

    return $languages;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#title' => $this->t('Google search API key'),
      '#type' => 'key_select',
      '#default_value' => $this->configuration['api_key'],
      '#empty_value' => '',
      '#empty_option' => $this->t('- Debug mode -'),
      '#description' => '<br />' . $this->t('Debug mode will generate sample content for the search results.'),
    ];

    $all_languages = $this->getSupportedLanguages();
    $language_default = $this->languageManager->getDefaultLanguage()->getId();
    foreach($all_languages as $langcode => $language) {
      $element_key = 'search_engine_id_' . $language->getId();
      $form[$element_key] = [
        '#type' => 'textfield',
        '#default_value' => $this->configuration['search_engine_id_' . $language->getId()],
      ];

      if ($langcode === $language_default) {
        $form[$element_key]['#title'] = $this->t('Google search engine ID (@language, Default site language)', [
          '@language' => $language->getName(),
        ]);
        $form[$element_key]['#description'] = $this->t('This is used when there is no search engine configured for the current language.');
      }
      elseif ($langcode === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        $form[$element_key]['#title'] = $this->t('Google search engine ID (All languages)');
        $form[$element_key]['#description'] = $this->t('This is used when there is no search engine configured for the current language or the default site language.');
      }
      else {
        $form[$element_key]['#title'] = $this->t('Google search engine ID (@language)', [
          '@language' => $language->getName(),
        ]);
      }
    }

    $form['base_url'] = array(
      '#title' => $this->t('Search engine base url'),
      '#type' => 'textfield',
      '#description' => $this->t('The base URL to send the query to. Use this to override the default request to Google, useful for proxying the request.'),
      '#default_value' => $this->configuration['base_url'],
    );

    $form['miscellaneous'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Miscellaneous'),
    );

    $form['miscellaneous']['page_size'] = array(
      '#title' => $this->t('Page size'),
      '#type' => 'textfield',
      '#description' => $this->t('Number of results to display per page.'),
      '#default_value' => $this->configuration['page_size'],
      '#size' => 5,
      '#max_length' => 5,
    );

    $form['miscellaneous']['pager_size'] = array(
      '#title' => $this->t('Pager size'),
      '#type' => 'textfield',
      '#description' => $this->t('Number of pages to show in the pager. Input ONLY odd numbers like 5, 7 or 9 and NOT 6, 8 or 10, for example.'),
      '#default_value' => $this->configuration['pager_size'],
      '#size' => 5,
      '#max_length' => 5,
    );

    $form['miscellaneous']['images'] = array(
      '#title' => $this->t('Image Search'),
      '#type' => 'checkbox',
      '#description' => $this->t('Enable image search.'),
      '#default_value' => $this->configuration['images'],
    );

    $form['miscellaneous']['labels'] = array(
      '#title' => $this->t('Show labels'),
      '#type' => 'checkbox',
      '#description' => $this->t('Let the user filter the search result by labels. <a href=":search-labels">Read more about search labels</a>.', [':search-labels' => 'https://developers.google.com/custom-search/docs/ref_prebuiltlabels']),
      '#default_value' => $this->configuration['labels'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $keys = [];
    $languages = $this->getSupportedLanguages();
    foreach($languages as $language) {
      $keys[] = 'search_engine_id_' . $language->getId();
    }
    $defaults = [
      'api_key',
      'base_url',
      // @todo autocomplete
      // 'autocomplete',
      'page_size',
      'pager_size',
      'images',
      'labels',
      // @todo number_of_results
      // 'number_of_results',
      // @todo info
      // 'info',
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

    // Reconcile items per page with api max 10.
    $count = 0;
    $n = $page_size < self::MAX_NUM ? $page_size : self::MAX_NUM;
    for ($i = 0; $i < $page_size; $i += self::MAX_NUM) {
      $offset = $page * $page_size + $i;
      if (!$response = $this->getResults($n, $offset)) {
        break;
      }
      if (isset($response->items)) {
        $this->count = $response->searchInformation->totalResults;
        $items = array_merge($items, $response->items);
      }
      else {
        break;
      }
      if ($this->configuration['labels'] && !empty($response->context->facets)) {
        $this->labels = $response->context->facets;
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
   * @param string $search_type
   *   One of:
   *   - NULL (regular search).
   *   - "image".
   *
   * @return object|null
   *   Decoded response from Google, or NULL on error.
   */
  protected function getResults($n = 1, $offset = 0, $search_type = NULL) {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $language_default = $this->languageManager->getDefaultLanguage()->getId();
    $any_language = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    $api_key = $this->keyRepository->getKey($this->configuration['api_key']);
    $query = $this->getParameters();
    $search_engine_id = NULL;
    if (!empty($this->configuration['search_engine_id_' . $language])) {
      // Language specific search engine id.
      $search_engine_id = $this->configuration['search_engine_id_' . $language];
    }
    elseif (!empty($this->configuration['search_engine_id_' . $language_default])) {
      // Default language search engine id.
      $search_engine_id = $this->configuration['search_engine_id_' . $language_default];
      $language = $language_default;
    }
    elseif (!empty($this->configuration['search_engine_id_' . $any_language])) {
      // Any language search engine id.
      $search_engine_id = $this->configuration['search_engine_id_' . $any_language];
    }

    // Exit, no search engine id defined.
    if (empty($search_engine_id)) {
      return NULL;
    }

    // make sure we actually have a base url
    $base_url = $this->configuration['base_url'];
    if (empty($base_url)) {
      $default_config = $this->defaultConfiguration();
      $base_url = $default_config['base_url'];
    }

    $keywords = $this->getKeywords();
    if (isset($query['label'])) {
      $keywords .= '+more:' . $query['label'];
    }

    $options = array(
      'query' => array(
        'q' => $keywords,
        'key' => !is_null($api_key) ? $api_key->getKeyValue() : NULL,
        'cx' => $search_engine_id,
        // hl: "interface language", also used to weight results.
        'hl' => $language,
        // lr: "language restrict", supposed to limit results to only the set
        // language, defined with a "lang_" prefix.
        'lr' => 'lang_'. $language,
        'start' => $offset + 1,
        'num' => $n,
      ),
    );

    if (@$query['type'] == 'image') {
      $options['query']['searchType'] = 'image';
    }

    if (!is_null($api_key)) {
      try {
        $response = $this->httpClient->get($base_url, $options);
        $json = $response->getBody();
      } catch (\Exception $e) {
        \Drupal::logger('gss')->error($e->getMessage());
        return NULL;
      }
    }
    else {
      $json = $this->generateSampleItems($base_url, $options);
    }
    return json_decode($json);
  }

  /**
   * Returns dummy search results for debug mode.
   *
   * @return string
   *   JSON data for debug mode.
   */
  protected function generateSampleItems($base_url, $options) {
    drupal_set_message($this->t('GSS Debug mode enabled, visit the <a href=":settings">search page settings</a> to disable.', [
      ':settings' => Url::fromRoute('entity.search_page.edit_form', ['search_page' => $this->searchPageId])
        ->toString(),
    ]), 'warning');
    $random = new Random();
    $results = [
      'kind'    => 'customsearch#search',
      'url'     => [
        'type'     => 'application/json',
        'template' => 'https://www.googleapis.com/customsearch/v1?q={searchTerms}&num={count?}&start={startIndex?}&lr={language?}&safe={safe?}&cx={cx?}&cref={cref?}&sort={sort?}&filter={filter?}&gl={gl?}&cr={cr?}&googlehost={googleHost?}&c2coff={disableCnTwTranslation?}&hq={hq?}&hl={hl?}&siteSearch={siteSearch?}&siteSearchFilter={siteSearchFilter?}&exactTerms={exactTerms?}&excludeTerms={excludeTerms?}&linkSite={linkSite?}&orTerms={orTerms?}&relatedSite={relatedSite?}&dateRestrict={dateRestrict?}&lowRange={lowRange?}&highRange={highRange?}&searchType={searchType}&fileType={fileType?}&rights={rights?}&imgSize={imgSize?}&imgType={imgType?}&imgColorType={imgColorType?}&imgDominantColor={imgDominantColor?}&alt=json',
      ],
      'queries' => [
        'request' => [
          'title'          => "Google Custom Seearch - {$options['query']['q']}",
          'totalResults'   => rand($options['query']['start'], 10000),
          'searchTerms'    => $options['query']['q'],
          'count'          => $options['query']['num'],
          'startIndex'     => $options['query']['start'],
          'inputEncoding'  => 'utf8',
          'outputEncoding' => 'utf8',
          'safe'           => 'off',
          'cx'             => $options['query']['cx'],
        ],
      ],
      'context' => [],
      'items'   => [],
    ];
    $results['searchInformation'] = [
      'totalResults' => $results['queries']['request']['totalResults'],
    ];

    // Items.
    for ($i = 0; $i < $options['query']['num']; $i++) {
      $title = $random->sentences(1);
      $link = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      $snippet = $random->paragraphs(1);

      $article = (object) [
        'name'        => $title,
        'description' => $snippet,
      ];
      $pagemap = new \stdClass();

      if (rand(0, 1)) {
        $image = '';
        $directory = "public://gss";
        if (file_prepare_directory($directory, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY)) {
          $image = $random->image("public://gss/gss_{$i}.jpg", '240x160', '240x160');
        }

        $pagemap->cse_thumbnail = [
          (object) [
            'width'  => 240,
            'height' => 160,
            'src'    => $image,
          ],
        ];
        $pagemap->cse_image = [
          (object) [
            'src' => $image,
          ],
        ];

        $article->image = $image;
      }

      $pagemap->article = [$article];

      $results['items'][$i] = [
        'kind'             => 'customsearch#result',
        'title'            => $title,
        'htmlTitle'        => Html::escape($title),
        'link'             => $link,
        'display_link'     => $link,
        'snippet'          => $snippet,
        'htmlSnippet'      => Html::escape($snippet),
        'cacheId'          => $random->string(),
        'formattedUrl'     => $link,
        'htmlFormatterUrl' => Html::escape($link),
        'pagemap'          => $pagemap,
      ];
    }

    // Labels.
    if ($this->configuration['labels']) {
      $results['context']['facets'] = [];
      $labels = [];
      for ($i = 0; $i < rand(2, 4); $i++) {
        $label = $random->string();
        if (!isset($labels[$label])) {
          $results['context']['facets'][][] = [
            'label'         => $label,
            'anchor'        => $label,
            'label_with_op' => "more:{$label}",
          ];
        }
        $labels[$label] = TRUE;
      }

      $query = $this->getParameters();
      if (isset($query['label']) && !isset($labels[$query['label']])) {
        $results['context']['facets'][][] = [
          'label'         => $query['label'],
          'anchor'        => $query['label'],
          'label_with_op' => "more:{$query['label']}",
        ];
      }
    }

    return json_encode($results);
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
      $extra = $this->moduleHandler->invokeAll('gss_search_result', [$item]);

      $results[] = [
        'link'     => $item->link,
        'type'     => NULL,
        'title'    => $item->title,
        'node'     => NULL,
        'extra'    => $extra,
        'score'    => NULL,
        'snippet'  => [
          '#markup' => $item->htmlSnippet,
        ],
        'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
      ];
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();

    $built = [];
    foreach ($results as $result) {
      $built[] = [
        '#theme'     => "search_result__{$this->getPluginId()}",
        '#result'    => $result,
        '#plugin_id' => $this->getPluginId(),
      ];
    }

    return $built;
  }

  /**
   * Gets render array for search option links.
   */
  public function getSearchOptions(Request $request) {
    $options = [];

    if ($this->configuration['images']) {
      $query = $this->getParameters();
      $active = $query['type'] == 'image';
      $query['type'] = 'image';
      $url = Url::createFromRequest($request);
      $url->setOption('query', $query);
      $url->setOption('attributes', $active ? ['class' => ['is-active']] : []);
      $options['images'] = [
        '#title' => $this->t('Images'),
        '#type' => 'link',
        '#url' => $url,
      ];
    }

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
  /**
   * Gets render array for labels.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A render array.
   */
  public function getLabels(Request $request) {
    // Don't output anything if there are no labels to display.
    if (empty($this->labels)) {
      return [];
    }

    // Generate a URL for the current search page and extract the active label,
    // if any.
    $url = Url::createFromRequest($request);
    $search_query_parameters = $this->getParameters();
    $active_label = NULL;
    if (isset($search_query_parameters['label']) && $search_query_parameters['label']) {
      $active_label = $search_query_parameters['label'];
      unset($search_query_parameters['label']);
    }

    $build = [
      '#theme'              => 'item_list__gss_labels',
      '#title'              => $this->t('Show only results of type:'),
      '#wrapper_attributes' => ['class' => ['search-labels']],
      '#context'            => ['list_style' => 'comma-list'],
    ];

    // Add the 'All results' option.
    $build['#items'][] = [
      '#title' => $this->t('All results'),
      '#type'  => 'link',
      '#url'   => $url->setOption('query', $search_query_parameters),
    ];

    // Add each label as an option.
    foreach ($this->labels as $facet_set) {
      foreach ($facet_set as $facet) {
        $url = clone $url;
        // Add the current label as a search parameter.
        $search_query_parameters['label'] = $facet->label;
        $url->setOption('query', $search_query_parameters);
        $url->setOption('attributes', ($active_label == $facet->label) ? ['class' => ['is-active']] : []);

        $build['#items'][] = [
          '#title' => $this->t($facet->anchor),
          '#type'  => 'link',
          '#url'   => $url,
        ];
      }
    }

    return $build;
  }

}
