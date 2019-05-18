<?php

namespace Drupal\real_estate_openimmo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\real_estate_openimmo\OpenImmoQuery;

/**
 * Defines the OpenImmo Source entity.
 *
 * @ConfigEntityType(
 *   id = "real_estate_openimmo",
 *   label = @Translation("OpenImmo Source"),
 *   handlers = {
 *     "list_builder" = "Drupal\real_estate_openimmo\OpenImmoListBuilder",
 *     "form" = {
 *       "add" = "Drupal\real_estate_openimmo\Form\OpenImmoForm",
 *       "edit" = "Drupal\real_estate_openimmo\Form\OpenImmoForm",
 *       "delete" = "Drupal\real_estate_openimmo\Form\OpenImmoDeleteForm",
 *       "queries-list" = "Drupal\real_estate_openimmo\Form\OpenImmoQueriesList",
 *       "add-query" = "Drupal\real_estate_openimmo\Form\OpenImmoQueryAddForm",
 *       "edit-query" = "Drupal\real_estate_openimmo\Form\OpenImmoQueryEditForm",
 *       "delete-query" = "Drupal\real_estate_openimmo\Form\OpenImmoQueryDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\real_estate_openimmo\OpenImmoHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "source",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/real_estate_openimmo/{real_estate_openimmo}",
 *     "add-form" = "/admin/structure/real_estate_openimmo/add",
 *     "edit-form" = "/admin/structure/real_estate_openimmo/{real_estate_openimmo}/edit",
 *     "delete-form" = "/admin/structure/real_estate_openimmo/{real_estate_openimmo}/delete",
 *     "collection" = "/admin/structure/real_estate_openimmo",
 *     "queries-list" = "/admin/real_estate/config/openimmo/query/{real_estate_openimmo}",
 *   }
 * )
 */
class OpenImmo extends ConfigEntityBase implements OpenImmoInterface {

  /**
   * The OpenImmo Source ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The OpenImmo Source label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Feed Type.
   *
   * @var string
   */
  protected $feed_type;

  /**
   * The Feed Config.
   *
   * @var array
   */
  protected $feed_config = [];

  /**
   * The Queries.
   *
   * @var string
   */
  protected $queries = [];

  /**
   * {@inheritdoc}
   */
  public function getFeedType() {
    return $this->feed_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setFeedType($feed_type) {
    $this->feed_type = $feed_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeedConfig() {
    return $this->feed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function addFeedConfig(array $feed_config) {
    $this->feed_config = $feed_config;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addQuery($query_id, $label, $key_field, $entity, $select) {
    if (isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' already exists for the source '{$this->id()}'");
    }
    if (preg_match('/[^a-z0-9_]+/', $query_id)) {
      throw new \InvalidArgumentException("The query ID '$query_id' must contain only lowercase letters, numbers, and underscores");
    }
    $this->queries[$query_id] = [
      'label' => $label,
    // todo: should be determined next weight value.
      'weight' => 0,
      'key_field' => $key_field,
      'entity' => $entity,
      'select' => $select,
    ];
    ksort($this->queries);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasQuery($query_id) {
    return isset($this->queries[$query_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueries($query_ids = NULL) {
    if ($query_ids === NULL) {
      $query_ids = array_keys($this->queries);
    }

    $queries = array_combine($query_ids, array_map([$this, 'getQuery'], $query_ids));
    if (count($queries) > 1) {
      // Sort queries by weight and then label.
      $weights = $labels = [];
      foreach ($queries as $id => $query) {
        $weights[$id] = $query->weight();
        $labels[$id] = $query->label();
      }
      array_multisort(
        $weights, SORT_NUMERIC, SORT_ASC,
        $labels, SORT_NATURAL, SORT_ASC
      );
      $queries = array_replace($weights, $queries);
    }
    return $queries;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($query_id) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in the source '{$this->id()}'");
    }
    $query = new OpenImmoQuery(
      $this,
      $query_id,
      $this->queries[$query_id]['label'],
      $this->queries[$query_id]['weight'],
      $this->queries[$query_id]['key_field'],
      $this->queries[$query_id]['entity'],
      $this->queries[$query_id]['select']
    );
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryLabel($query_id, $label) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in source '{$this->id()}'");
    }
    $this->queries[$query_id]['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryWeight($query_id, $weight) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in source '{$this->id()}'");
    }
    $this->queries[$query_id]['weight'] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuerySelect($query_id, $select) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in source '{$this->id()}'");
    }
    $this->queries[$query_id]['select'] = $select;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryKeyField($query_id, $key_field) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in source '{$this->id()}'");
    }
    $this->queries[$query_id]['key_field'] = $key_field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryEntity($query_id, $entity) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in source '{$this->id()}'");
    }
    $this->queries[$query_id]['entity'] = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQuery($query_id) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in source '{$this->id()}'");
    }

    unset($this->queries[$query_id]);

    return $this;
  }

}
