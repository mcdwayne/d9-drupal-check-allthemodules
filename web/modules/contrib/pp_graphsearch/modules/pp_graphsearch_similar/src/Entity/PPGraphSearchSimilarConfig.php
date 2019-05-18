<?php

/**
 * @file Contains \Drupal\pp_graphsearch_similar\Entity\PPGraphSearchSimilarConfig.
 */

namespace Drupal\pp_graphsearch_similar\Entity;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\semantic_connector\SemanticConnector;

/**
 * @ConfigEntityType(
 *   id ="pp_graphsearch_similar",
 *   label = @Translation("PoolParty GraphSearch SeeAlso widget"),
 *   handlers = {
 *     "list_builder" = "Drupal\pp_graphsearch_similar\PPGraphSearchSimilarConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigConnectionForm",
 *       "add" = "Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigConnectionForm",
 *       "edit" = "Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigConnectionForm",
 *       "edit_config" = "Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigForm",
 *       "delete" = "Drupal\pp_graphsearch_similar\Form\PPGraphSearchSimilarConfigDeleteForm"
 *     }
 *   },
 *   config_prefix = "pp_graphsearch_similar",
 *   admin_permission = "administer pp_graphsearch",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/semantic-drupal/pp-graphsearch/pp-graphsearch-similar/configurations/{pp_graphsearch}/delete",
 *     "edit-form" = "/admin/config/semantic-drupal/pp-graphsearch/pp-graphsearch-similar/configurations/{pp_graphsearch}",
 *     "collection" = "/admin/config/semantic-drupal/pp-graphsearch/pp-graphsearch-similar/",
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
class PPGraphSearchSimilarConfig extends ConfigEntityBase implements PPGraphSearchSimilarConfigInterface {
  protected $id;
  protected $search_space_id;
  protected $title;
  protected $connection_id;
  protected $connection;
  protected $config;

  /**
   * Constructor of the PPGraphSearchSimilarConfig-class.
   *
   * {@inheritdoc|}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    if (is_null($this->id())) {
      $this->connection_id = 0;
      $this->config = array();
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
      'max_items' => 5,
    );
  }

  /**
   * Helper function to check whether an pp_graphsearch_similar entity with a specific
   * ID exists.
   *
   * @param string $id
   *   The ID to check if there is an entity for.
   *
   * @return bool
   *   TRUE if an entity with this ID already exists, FALSE if not.
   */
  public static function exist($id) {
    $entity_count = \Drupal::entityQuery('pp_graphsearch_similar')
      ->condition('id', $id)
      ->count()
      ->execute();
    return (bool) $entity_count;
  }
}