<?php

/**
 * @file Contains \Drupal\pp_graphsearch\Entity\PPGraphSearchConfig.
 */

namespace Drupal\pp_graphsearch\Entity;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\semantic_connector\SemanticConnector;

/**
 * @ConfigEntityType(
 *   id ="pp_graphsearch",
 *   label = @Translation("PoolParty GraphSearch configuration"),
 *   handlers = {
 *     "list_builder" = "Drupal\pp_graphsearch\PPGraphSearchConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\pp_graphsearch\Form\PPGraphSearchConfigConnectionForm",
 *       "add" = "Drupal\pp_graphsearch\Form\PPGraphSearchConfigConnectionForm",
 *       "edit" = "Drupal\pp_graphsearch\Form\PPGraphSearchConfigConnectionForm",
 *       "edit_config" = "Drupal\pp_graphsearch\Form\PPGraphSearchConfigForm",
 *       "delete" = "Drupal\pp_graphsearch\Form\PPGraphSearchConfigDeleteForm",
 *       "clone" = "Drupal\pp_graphsearch\Form\PPGraphSearchConfigCloneForm"
 *     }
 *   },
 *   config_prefix = "pp_graphsearch",
 *   admin_permission = "administer pp_graphsearch",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/semantic-drupal/pp-graphsearch/configurations/{pp_graphsearch}/delete",
 *     "edit-form" = "/admin/config/semantic-drupal/pp-graphsearch/configurations/{pp_graphsearch}",
 *     "collection" = "/admin/config/semantic-drupal/pp-graphsearch/",
 *   },
 *   config_export = {
 *     "title",
 *     "id",
 *     "connection_id",
 *     "search_space_id",
 *     "config",
 *   }
 * )
 */
class PPGraphSearchConfig extends ConfigEntityBase implements PPGraphSearchConfigInterface {
  protected $id;
  protected $search_space_id;
  protected $title;
  protected $connection_id;
  protected $connection;
  protected $config;

  /**
   * Constructor of the SonrWebminingConfigurationSet-class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if (is_null($this->id())) {
      $this->connection_id = 0;
      $this->config = array();
    }
    else {
      /*$connection_overrides = \Drupal::config('semantic_connector.settings')->get('override_connections');
      if (isset($connection_overrides[$this->id()])) {
        $overrides = $connection_overrides[$this->id()];
        if (isset($overrides['connection_id'])) {
          $this->connection_id = $overrides['connection_id'];
        }
        if (isset($overrides['search_space_id'])) {
          $this->search_space_id = $overrides['search_space_id'];
        }
        if (isset($overrides['title'])) {
          $this->title = $overrides['title'];
        }
      }*/
    }

    $this->connection = SemanticConnector::getConnection('pp_server', $this->connection_id);

    // Merge the Config with the default ones.
    $this->config = $this->config + self::getDefaultConfig();
  }

  /**
   * {@inheritdoc|}
   */
  public function getSearchSpaceId() {
    return $this->search_space_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function setSearchSpaceId($search_space_id) {
    $this->search_space_id = $search_space_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * {@inheritdoc|}
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConnectionId() {
    return $this->connection_id;
  }

  /**
   * {@inheritdoc|}
   */
  public function setConnectionId($connection_id) {
    $this->connection_id = $connection_id;
    $this->connection = SemanticConnector::getConnection('pp_server', $this->connection_id);
  }

  /**
   * {@inheritdoc|}
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * {@inheritdoc|}
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * {@inheritdoc|}
   */
  public function setConfig($config) {
    $this->config = $config + self::getDefaultConfig();
  }

  /**
   * {@inheritdoc|}
   */
  public static function getDefaultConfig() {
    return array(
      // Result settings.
      'items_per_page' => 10,
      'show_results_count' => FALSE,
      'summary_max_chars' => 200,
      'date_format' => 'short',
      'link_target' => '_blank',
      'show_tags' => TRUE,
      'tags_max_items' => 10,
      'show_similar' => TRUE,
      'similar_max_items' => 5,
      'show_sentiment' => FALSE,
      'first_page_only' => FALSE,
      'cache_lifetime' => 600,
      // Filter settings.
      'separate_blocks' => FALSE,
      'facets_to_show' => array(),
      'facet_max_items' => 10,
      'hide_empty_facet' => TRUE,
      'time_filter' => NULL,
      'time_filter_years' => NULL,
      'add_trends' => FALSE,
      'components_order' => array('facets', 'time', 'reset', 'trends'),
      // Search bar settings.
      'show_searchbar' => TRUE,
      'show_block_searchbar' => FALSE,
      'placeholder' => 'Search...',
      'ac_max_suggestions' => 10,
      'ac_min_chars' => 3,
      'ac_add_matching_label' => TRUE,
      'ac_add_context' => TRUE,
      'ac_add_facet_name' => FALSE,
      'show_facetbox' => TRUE,
      'search_type' => 'concept free-term',
      // Trends settings.
      'trends_title' => 'Trends',
      'trends_description' => '',
      'trends_chart_type' => 'simple_moving_average',
      'trends_colors' => '',
      // Other settings.
      'use_css_file' => TRUE,
      'add_rss_functionality' => FALSE,
    );
  }

  /**
   * Helper function to check whether an pp_graphsearch entity with a specific
   * ID exists.
   *
   * @param string $id
   *   The ID to check if there is an entity for.
   *
   * @return bool
   *   TRUE if an entity with this ID already exists, FALSE if not.
   */
  public static function exist($id) {
    $entity_count = \Drupal::entityQuery('pp_graphsearch')
      ->condition('id', $id)
      ->count()
      ->execute();
    return (bool) $entity_count;
  }
}