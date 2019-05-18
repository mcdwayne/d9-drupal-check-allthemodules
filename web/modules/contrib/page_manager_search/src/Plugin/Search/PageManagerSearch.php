<?php

/**
 * @file
 *
 * Contains \Drupal\page_manager_search\Plugin\Search\PageManagerSearch.
 */

namespace Drupal\page_manager_search\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Render\RendererInterface;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\Plugin\SearchPluginBase;
use Drupal\search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchPlugin(
 *   id = "page_manager_search",
 *   title = @Translation("PageManagerSearch Search")
 * )
 */
class PageManagerSearch extends SearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A module handler object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A config object for 'search.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Drupal account to use for checking for access to advanced search.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The Renderer service to format the username and entity.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * An array of additional rankings from hook_ranking().
   *
   * @var array
   */
  protected $rankings;

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
      $container->get('module_handler'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs
   * \Drupal\page_manager_search\Plugin\Search\PageManagerSearch.
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler object.
   * @param \Drupal\Core\Config\Config $search_settings
   *   A config object for 'search.settings'.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The $account object to use for checking for access to advanced search.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, Config $search_settings, LanguageManagerInterface $language_manager, RendererInterface $renderer, AccountInterface $account = NULL) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->searchSettings = $search_settings;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
    $this->account = $account;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->addCacheTags(['page_manager_search_list']);
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
  public function isSearchExecutable() {
    return !empty($this->keywords) || (isset($this->searchParameters['f']) && count($this->searchParameters['f']));
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
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

    $keys = $this->keywords;

    // Build matching conditions.
    $query = $this->database
      ->select('search_index', 'i', ['target' => 'replica'])
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $query
      ->searchExpression($keys, $this->getPluginId());

    $parameters = $this->getParameters();
    if (!empty($parameters['f']) && is_array($parameters['f'])) {
      $filters = [];
      $pattern = '/^(' . implode('|', array_keys($this->advanced)) . '):([^ ]*)/i';
      foreach ($parameters['f'] as $item) {
        if (preg_match($pattern, $item, $m)) {
          // Use the matched value as the array key to eliminate duplicates.
          $filters[$m[1]][$m[2]] = $m[2];
        }
      }

      // Now turn these into query conditions. This assumes that everything in
      // $filters is a known type of advanced search as defined in
      // $this->advanced.
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

    // Add the ranking expressions.
    $this->addPageManagerSearchRanking($query);

    // Run the query.
    $find = $query
      // Add the language code of the indexed item to the result of the query,
      // since the entity will be rendered using the respective language.
      ->fields('i', ['langcode'])
      // And since SearchQuery makes these into GROUP BY queries, if we add
      // a field, for PostgreSQL we also need to make it an aggregate or a
      // GROUP BY. In this case, we want GROUP BY.
      ->groupBy('i.langcode')
      ->limit(10)
      ->execute();

    // Check query status and set messages if needed.
    $status = $query->getStatus();

    if ($status & SearchQuery::EXPRESSIONS_IGNORED) {
      drupal_set_message($this->t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', ['@count' => $this->searchSettings->get('and_or_limit')]), 'warning');
    }

    if ($status & SearchQuery::LOWER_CASE_OR) {
      drupal_set_message($this->t('Search for either of the two terms with uppercase OR. For example, cats OR dogs.'), 'warning');
    }

    if ($status & SearchQuery::NO_POSITIVE_KEYWORDS) {
      drupal_set_message($this->formatPlural($this->searchSettings->get('index.minimum_word_size'), 'You must include at least one keyword to match in the content, and punctuation is ignored.', 'You must include at least one keyword to match in the content. Keywords must be at least @count characters, and punctuation is ignored.'), 'warning');
    }

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
    $results = [];

    $entity_storage = $this->entityManager->getStorage('page_manager_search');
    $entity_render = $this->entityManager->getViewBuilder('page_manager_search');
    $keys = $this->keywords;

    foreach ($found as $item) {
      // Render the PageManagerSearch entity.
      $entity = $entity_storage->load($item->sid)
        ->getTranslation($item->langcode);
      $build = $entity_render->view($entity, 'default', $item->langcode);

      unset($build['#theme']);

      // Build the snippet.
      $rendered = $this->renderer->renderPlain($build);
      $this->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));

      $extra = $this->moduleHandler->invokeAll('page_manager_search', [$entity]);
      $path = $entity->get('path_to_page')->getValue();

      $result = [
        'link' => $path[0]['value'],
        'type' => 'Page Manager Search',
        'title' => $entity->get('title')->getString(),
        'page_manager_search' => $entity,
        'extra' => $extra,
        'score' => $item->calculated_score,
        'snippet' => search_excerpt($keys, $rendered, $item->langcode),
        'langcode' => $entity->language()->getId(),
      ];

      $this->addCacheableDependency($entity);

      $results[] = $result;

    }
    return $results;
  }

  /**
   * Removes results data from the build array.
   *
   * This information is being removed from the rendered entity that is used to
   * build the search result snippet.
   *
   * @param array $build
   *   The build array.
   *
   * @return array
   *   The modified build array.
   */
  public function removeFromSnippet(array $build) {
    return $build;
  }

  /**
   * Adds the configured rankings to the search query.
   *
   * @param SelectExtender $query
   *   A query object that has been extended with the FcoSearch DB Extender.
   */
  protected function addPageManagerSearchRanking(SelectExtender $query) {
    if ($ranking = $this->getRankings()) {
      $tables = &$query->getTables();
      foreach ($ranking as $rank => $values) {
        if (isset($this->configuration['rankings'][$rank]) && !empty($this->configuration['rankings'][$rank])) {
          $entity_rank = $this->configuration['rankings'][$rank];
          // If the table defined in the ranking isn't already joined, add it.
          if (isset($values['join']) && !isset($tables[$values['join']['alias']])) {
            $query->addJoin($values['join']['type'], $values['join']['table'], $values['join']['alias'], $values['join']['on']);
          }
          $arguments = isset($values['arguments']) ? $values['arguments'] : [];
          $query->addScore($values['score'], $arguments, $entity_rank);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    // Interpret the cron limit setting as the maximum number of entities to
    // index per cron run.
    $limit = (int) $this->searchSettings->get('index.cron_limit');

    $result = $this->database->queryRange("SELECT pms.id, MAX(sd.reindex) 
      FROM {page_manager_search} pms 
      LEFT JOIN {search_dataset} sd ON sd.sid = pms.id AND sd.type = :type 
      WHERE sd.sid IS NULL OR sd.reindex <> 0 
      GROUP BY pms.id 
      ORDER BY MAX(sd.reindex) is null DESC, MAX(sd.reindex) ASC, pms.id ASC",
      0, $limit, [':type' => $this->getPluginId()], ['target' => 'page_manager_search']);

    $rids = $result->fetchCol();
    if (!$rids) {
      return;
    }

    $entity_storage = $this->entityManager->getStorage('page_manager_search');

    foreach ($entity_storage->loadMultiple($rids) as $entity) {
      $this->indexPageManagerSearch($entity);
    }
  }

  /**
   * Indexes a single Page Manager Search.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  protected function indexPageManagerSearch(ContentEntityInterface $entity) {
    $languages = $entity->getTranslationLanguages();
    $entity_render = $this->entityManager->getViewBuilder('page_manager_search');

    foreach ($languages as $language) {
      $entity = $entity->getTranslation($language->getId());
      $build = $entity_render->view($entity, 'search_index', $language->getId());

      unset($build['#theme']);

      // Add the title to text so it is searchable.
      $build['search_title'] = [
        '#prefix' => '',
        '#plain_text' => $entity->get('title')->getString(),
        '#suffix' => '',
        '#weight' => -1000,
      ];
      $text = $entity->get('content')->getString();

      // Update index, using search index "type" equal to the plugin ID.
      search_index($this->getPluginId(), $entity->id(), $language->getId(), $text);
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

    $total = $this->database->query('SELECT COUNT(*) FROM {page_manager_search}')
      ->fetchField();
    $remaining = $this->database->query("SELECT COUNT(DISTINCT pms.id) 
      FROM {page_manager_search} pms 
      LEFT JOIN {search_dataset} sd ON sd.sid = pms.id AND sd.type = :type 
      WHERE sd.sid IS NULL OR sd.reindex <> 0", [':type' => $this->getPluginId()])
      ->fetchField();

    return ['remaining' => $remaining, 'total' => $total];
  }

  /**
   * Gathers ranking definitions from hook_ranking().
   *
   * @return array
   *   An array of ranking definitions.
   */
  protected function getRankings() {
    if (!$this->rankings) {
      $this->rankings = $this->moduleHandler->invokeAll('ranking');
    }
    return $this->rankings;
  }

}
