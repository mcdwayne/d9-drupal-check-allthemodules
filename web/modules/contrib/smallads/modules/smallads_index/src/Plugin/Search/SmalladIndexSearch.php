<?php

namespace Drupal\smallads_index\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\Search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "smallad_index_search",
 *   title = @Translation("Content")
 * )
 */
class SmalladIndexSearch extends ConfigurableSearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

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
   * A module manager object.
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
   * The Renderer service to format the username and node.
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
   * The list of options and info for advanced search filters.
   *
   * Each entry in the array has the option as the key and for its value, an
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
    'type' => ['column' => 'n.type'],
    'language' => ['column' => 'i.langcode'],
    'author' => ['column' => 'n.uid'],
    'term' => ['column' => 'ti.tid', 'join' => ['table' => 'taxonomy_index', 'alias' => 'ti', 'condition' => 'n.nid = ti.nid']],
  ];

  /**
   * A constant for setting and checking the query string.
   */
  const ADVANCED_FORM = 'advanced-form';

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module manager object.
   * @param \Drupal\Core\Config\Config $search_settings
   *   A config object for 'search.settings'.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
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

    $this->addCacheTags(['node_list']);
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
    // Node search is executable if we have keywords or an advanced parameter.
    // At least, we should parse out the parameters and see if there are any
    // keyword matches in that case, rather than just printing out the
    // "Please enter keywords" message.
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
    return $results;
  }

  /**
   * Removes the submitted by information from the build array.
   *
   * This information is being removed from the rendered node that is used to
   * build the search result snippet. It just doesn't make sense to have it
   * displayed in the snippet.
   *
   * @param array $build
   *   The build array.
   *
   * @return array
   *   The modified build array.
   */
  public function removeSubmittedInfo(array $build) {
    unset($build['created']);
    unset($build['uid']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {

    return $query;
  }


}
