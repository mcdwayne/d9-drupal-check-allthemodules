<?php

namespace Drupal\smallads\Plugin\Search;

use Drupal\smallads\Entity\SmalladType;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Render\RendererInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for smallad entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "smallad_search",
 *   title = @Translation("Ads")
 * )
 *
 * @note to make this search configurable, extend ConfigurableSearchPluginBase
 */
class SmalladSearch extends SearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Ad entity controllers.
   */
  protected $smallAdStorage;
  protected $smallAdViewBuilder;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Renderer service to format the username and node.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;


  /**
   * The list of options and info for advanced search filters.
   *
   * Each entry in the array has the option as the key and and for its value, an
   * array that determines how the value is matched in the database query. The
   * possible keys in that array are:
   * - column: (required) Name of the database column to match against.
   * - join: (optional) Information on a table to join. By default the data is
   *   matched against the {node_field_data} table.
   * - operator: (optional) OR or AND, defaults to OR.
   *
   * @var array
   */
  protected $advanced = [
    'language' => [
      'column' => 'i.langcode',
    ],
    'owner' => [
      'column' => 'sm.uid',
    ],
    'scope' => [
      'column' => 'sm.scope',
    ],
    'type' => [
      'column' => 'smt.entity_id',
      'join' => [
        'table' => 'smallad__type',
        'alias' => 'smt',
        'condition' => 'sm.smid = smt.type_target_id',
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('current_user')
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
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, RendererInterface $renderer) {
    $this->database = $database;
    $this->smallAdViewBuilder = $entity_manager->getViewBuilder('smallad');
    $this->smallAdStorage = $entity_manager->getStorage('smallad');
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'post smallad');
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    // Borrowed from node_search.
    return !empty($this->keywords) || (isset($this->searchParameters['f']) && count($this->searchParameters['f']));
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'smallad_search';
  }

  /**
   * {@inheritdoc}
   *
   * @note borrowed from node_search. looks rather generic though
   */
  public function execute() {
    if ($this->isSearchExecutable()) {
      $results = $this->findResults();
      if ($results) {
        return $this->prepareResults($results);
      }
    }

    return [];
  }

  /**
   * Queries to find search results, and sets status messages.
   *
   * This method can assume that $this->isSearchExecutable() has already been
   * checked and returned TRUE.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Results from search query execute() method, or NULL if the search
   *   failed.
   */
  protected function findResults() {
    // Build matching conditions.
    $query = $this->database
      ->select('search_index', 'i', array('target' => 'replica'))
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query->join('smallad_field_data', 'sm', 'sm.smid = i.sid');
    $query->condition('sm.scope', 0, '>')
      ->searchExpression($this->keywords, $this->getPluginId());

    // Handle advanced search filters in the f query string.
    // \Drupal::request()->query->get('f') is an array that looks like this in
    // the URL: ?f[]=type:page&f[]=term:27&f[]=term:13&f[]=langcode:en
    // So $parameters['f'] looks like:
    // array('type:page', 'term:27', 'term:13', 'langcode:en');
    // We need to parse this out into query conditions, some of which go into
    // the keywords string, and some of which are separate conditions.
    $parameters = $this->getParameters();
    if (!empty($parameters['f']) && is_array($parameters['f'])) {
      $filters = array();
      // Match any query value that is an expected option and a value
      // separated by ':' like 'term:27'.
      $pattern = '/^(' . implode('|', array_keys($this->advanced)) . '):([^ ]*)/i';
      foreach ($parameters['f'] as $item) {
        if (preg_match($pattern, $item, $m)) {
          // Use the matched value as the array key to eliminate duplicates.
          $filters[$m[1]][$m[2]] = $m[2];
        }
      }

      // Now turn these into query conditions. This assumes that everything in
      // $filters is a known type of advanced search.
      foreach ($filters as $option => $matched) {
        $info = $this->advanced[$option];
        // Insert additional conditions. By default, all use the OR operator.
        $operator = empty($info['operator']) ? 'OR' : $info['operator'];
        $where = new Condition($operator);
        foreach ($matched as $value) {
          $where->condition($info['column'], $value);
        }
        $query->condition($where);
        if (!empty($info['join'])) {
          $query->join($info['join']['table'], $info['join']['alias'], $info['join']['condition']);
        }
      }
    }

    // This is where we tweak the query to reorder the search according to
    // search settings.
    if ($this->getConfiguration('comments')) {
      debug('Search is not tested on comments');
      // This is all just added from elsewhere not tested.
      $query->addJoin('LEFT', 'comment_entity_statistics', 'ces', "ces.entity_id = i.sid AND ces.entity_type = 'smallad' AND ces.field_name = 'comment'");
      $query->addScore('2.0 - 2.0 / (1.0 + ces.comment_count * (ROUND(5, 4)))');
      $query->addScore('i.relevance');
    }
    $find = $query
      ->fields('i', array('langcode'))
      ->groupBy('i.langcode')
      ->limit(10)
      ->execute();

    return $find;
  }

  /**
   * Prepares search results for rendering.
   *
   * @param \Drupal\Core\Database\StatementInterface $found
   *   Results found from a successful search query execute() method.
   *
   * @return array
   *   Array of search result item render arrays (empty array if no results).
   */
  protected function prepareResults(StatementInterface $found) {
    $results = array();
    $keys = $this->keywords;

    foreach ($found as $item) {
      $ad = $this->smallAdStorage->load($item->sid)->getTranslation($item->langcode);
      $build = ['#markup' => Html::escape($ad->label())]
        + $this->smallAdViewBuilder->view($ad, 'search_result', $item->langcode);

      unset($build['#theme']);
      // Not sure if more escaping is needed here
      $rendered = $this->renderer->render($build);

      // See template_preprocess_search_result
      // search result theming is not v good and not well documented in beta 11.
      $result = array(
        'link' => $ad->url(
          'canonical',
          [
            'absolute' => TRUE,
            'language' => $this->languageManager->getLanguage($item->langcode),
          ]
        ),
        'type' => $ad->type->entity->label(),// Does this need escaping?
        'title' => $ad->label(),
        'langcode' => $ad->language()->getId(),
        'date' => $ad->getChangedTime(),
        'score' => $item->calculated_score,
        'snippet' => search_excerpt($keys, $rendered, $item->langcode),
      );
      $results[] = $result;

    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    $result = $this->database->queryRange(
      "SELECT sm.smid, MAX(sd.reindex)
        FROM {smallad} sm
        LEFT JOIN {search_dataset} sd ON sd.sid = sm.smid AND sd.type = :type
        WHERE sd.sid IS NULL OR sd.reindex <> 0
        GROUP BY sm.smid
        ORDER BY MAX(sd.reindex) is null DESC, MAX(sd.reindex) ASC, sm.smid ASC",
      0,
      50,
      [':type' => $this->getPluginId()],
      ['target' => 'replica']
    );
    if ($smids = $result->fetchCol()) {
      foreach ($this->smallAdStorage->loadMultiple($smids) as $ad) {
        $this->indexAd($ad);
      }
    }
  }

  /**
   * Indexes a single ad.
   *
   * @param \Drupal\core\Entity\ContentEntityInterface $ad
   *   The smallad to index.
   */
  protected function indexAd(ContentEntityInterface $ad) {
    foreach ($ad->getTranslationLanguages() as $language) {
      $lang_id = $language->getId();
      $ad = $ad->getTranslation($lang_id);
      // Render it.
      $build = $this->smallAdViewBuilder->view($ad, 'search_index', $lang_id);

//      unset($build['#theme']);
      $rendered = $this->renderer->renderPlain($build);
      $text = '<h1>' . $ad->label($lang_id) . '</h1>' . $rendered; // Does this need escaping?
      // Update index, using search index "type" equal to the plugin ID.
      search_index($this->getPluginId(), $ad->id(), $lang_id, $text);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    search_index_clear($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    search_mark_for_reindex($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {
    $q = "SELECT COUNT(DISTINCT sm.smid)
      FROM {smallad} sm
      LEFT JOIN {search_dataset} sd ON sd.sid = sm.smid AND sd.type = :type
      WHERE sd.sid IS NULL OR sd.reindex <> 0";
    return [
      'remaining' => $this->database->query($q, [':type' => $this->getPluginId()])->fetchField(),
      'total' => $this->database->query('SELECT COUNT(*) FROM {smallad}')->fetchField(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    // Add advanced search keyword-related boxes.
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced search'),
      '#attributes' => array('class' => array('search-advanced')),
    ];
    $form['advanced']['keywords-fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Keywords'),
    ];
    $form['advanced']['keywords'] = [
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
    ];
    $form['advanced']['keywords-fieldset']['keywords']['or'] = [
      '#type' => 'textfield',
      '#title' => t('Containing any of the words'),
      '#size' => 30,
      '#maxlength' => 255,
    ];
    $form['advanced']['keywords-fieldset']['keywords']['phrase'] = [
      '#type' => 'textfield',
      '#title' => t('Containing the phrase'),
      '#size' => 30,
      '#maxlength' => 255,
    ];
    $form['advanced']['keywords-fieldset']['keywords']['negative'] = [
      '#type' => 'textfield',
      '#title' => t('Containing none of the words'),
      '#size' => 30,
      '#maxlength' => 255,
    ];

    foreach (SmalladType::loadMultiple() as $id => $type) {
      $types[$id] = $type->label();
    }

    $form['advanced']['types-fieldset'] = [
      '#type' => 'fieldset',
      '#title' => t('Types'),
    ];
    $form['advanced']['types-fieldset']['type'] = [
      '#type' => 'checkboxes',
      '#title' => t('Only of the type(s)'),
      '#prefix' => '<div class="criterion">',
      '#suffix' => '</div>',
      '#options' => $types,
    ];
    $form['advanced']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Advanced search'),
      '#prefix' => '<div class="action">',
      '#suffix' => '</div>',
      '#weight' => 100,
    ];

    // Add languages - this is rather cumbersome.
    $language_options = array();
    $language_list = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($language_list as $langcode => $language) {
      // Make locked languages appear special in the list.
      $language_options[$langcode] = $language->isLocked() ? t('- @name -', array('@name' => $language->getName())) : $language->getName();
    }
    if (count($language_options) > 1) {
      $form['advanced']['lang-fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => t('Languages'),
      );
      $form['advanced']['lang-fieldset']['language'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Languages'),
        '#prefix' => '<div class="criterion">',
        '#suffix' => '</div>',
        '#options' => $language_options,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    // Read keyword and advanced search information from the form values,
    // and put these into the GET parameters.
    $keys = trim($form_state->getValue('keys'));

    // Collect extra filters.
    $filters = array();
    if ($form_state->hasValue('type') && is_array($form_state->getValue('type'))) {
      foreach ($form_state->getValue('type') as $type) {
        if ($type) {
          $filters[] = 'type:' . $type;
        }
      }
    }

    if ($form_state->hasValue('term') && is_array($form_state->getValue('term'))) {
      foreach ($form_state->getValue('term') as $term) {
        $filters[] = 'term:' . $term;
      }
    }
    if ($form_state->hasValue('language') && is_array($form_state->getValue('language'))) {
      foreach ($form_state->getValue('language') as $language) {
        if ($language) {
          $filters[] = 'language:' . $language;
        }
      }
    }
    if ($form_state->getValue('or') != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('or'), $matches)) {
        $keys .= ' ' . implode(' OR ', $matches[1]);
      }
    }
    if ($form_state->getValue('negative') != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('negative'), $matches)) {
        $keys .= ' -' . implode(' -', $matches[1]);
      }
    }
    if ($form_state->getValue('phrase') != '') {
      $keys .= ' "' . str_replace('"', ' ', $form_state->getValue('phrase')) . '"';
    }
    $keys = trim($keys);

    // Put the keywords and advanced parameters into GET parameters. Make sure
    // to put keywords into the query even if it is empty, because the page
    // controller uses that to decide it's time to check for search results.
    $query = ['keys' => $keys];
    if ($filters) {
      $query['f'] = $filters;
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = array(
      'order' => 'relevance',
      'promimity' => 0,
      'comments' => 0,
      'ratings' => 0,
    );
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['order'] = [
      '#title' => $this->t('Result order', [], ['context' => 'search']),
      // '#description' => 'only intended to work with the geo modules'.
      '#type' => 'radios',
      '#options' => [
        'relevance' => $this->t('Keyword relevance'),
        'comments' => $this->t('Number of comments'),
      ],
      '#default_value' => $this->configuration['order'],
    ];
    return $form;

    $form['proximity'] = [
      '#title' => $this->t('Rank using proximity'),
      '#type' => 'checkbox',
      '#description' => 'not working yet, and even then, only with the geo modules',
      '#default_value' => $this->configuration['proximity'],
    ];
    $form['comments'] = [
      '#title' => $this->t('Prioritise ads with more comments'),
      '#type' => 'checkbox',
      '#description' => 'not working yet, and not needed on small sites',
      '#default_value' => $this->configuration['comments'],
    ];
    $form['ratings'] = [
      '#title' => $this->t('Prioritise ads with better ratings'),
      '#type' => 'checkbox',
      '#description' => "rating system isn't implemented at all!",
      '#default_value' => $this->configuration['ratings'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['proximity'] = $form_state->getValue('promimity');
    $this->configuration['comments'] = $form_state->getValue('comments');
    $this->configuration['ratings'] = $form_state->getValue('ratings');
  }

  /**
   *
   */
  protected function getConfiguration($setting) {
    return @$this->configuration[$setting] ? : $this->defaultConfiguration()[$setting];
  }

}
