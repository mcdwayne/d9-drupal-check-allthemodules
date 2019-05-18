<?php

namespace Drupal\real_estate_rets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\real_estate_rets\RetsQuery;

/**
 * Defines the RETS Connection entity.
 *
 * @ConfigEntityType(
 *   id = "real_estate_rets_connection",
 *   label = @Translation("RETS Connection"),
 *   handlers = {
 *     "list_builder" = "Drupal\real_estate_rets\RetsConnectionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\real_estate_rets\Form\RetsConnectionForm",
 *       "edit" = "Drupal\real_estate_rets\Form\RetsConnectionForm",
 *       "delete" = "Drupal\real_estate_rets\Form\RetsConnectionDeleteForm",
 *       "queries-list" = "Drupal\real_estate_rets\Form\RetsConnectionQueriesList",
 *       "add-query" = "Drupal\real_estate_rets\Form\RetsConnectionQueryAddForm",
 *       "edit-query" = "Drupal\real_estate_rets\Form\RetsConnectionQueryEditForm",
 *       "delete-query" = "Drupal\real_estate_rets\Form\RetsConnectionQueryDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\real_estate_rets\RetsConnectionHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "real_estate_rets_connection",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/real-estate/config/rets-connection/{real_estate_rets_connection}",
 *     "add-form" = "/admin/real-estate/config/rets-connection/add",
 *     "edit-form" = "/admin/real-estate/config/rets-connection/{real_estate_rets_connection}/edit",
 *     "delete-form" = "/admin/real-estate/config/rets-connection/{real_estate_rets_connection}/delete",
 *     "collection" = "/admin/real-estate/config/rets-connection",
 *     "queries-list" = "/admin/real-estate/config/rets-connection/query/{real_estate_rets_connection}",
 *   }
 * )
 */
class RetsConnection extends ConfigEntityBase implements RetsConnectionInterface {

  /**
   * The RETS Connection ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The RETS Connection label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Login URL.
   *
   * @var string
   */
  protected $login_url;

  /**
   * The User Name.
   *
   * @var string
   */
  protected $username;

  /**
   * The Password.
   *
   * @var string
   */
  protected $password;

  /**
   * The Rets Version.
   *
   * @var string
   */
  protected $rets_version;

  /**
   * The User Agent.
   *
   * @var string
   */
  protected $user_agent;

  /**
   * The User Agent Password.
   *
   * @var string
   */
  protected $user_agent_password;

  /**
   * The HTTP Authentication.
   *
   * @var string
   */
  protected $http_authentication;

  /**
   * The Use POST Method.
   *
   * @var string
   */
  protected $use_post_method;

  /**
   * The Disable Follow Location.
   *
   * @var string
   */
  protected $disable_follow_location;

  /**
   * The Queries.
   *
   * @var string
   */
  protected $queries = [];

  /**
   * {@inheritdoc}
   */
  public function getLoginUrl() {
    return $this->login_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * {@inheritdoc}
   */
  public function getRetsVersion() {
    return $this->rets_version;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserAgent() {
    return $this->user_agent;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserAgentPassword() {
    return $this->user_agent_password;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpAuthentication() {
    return $this->http_authentication;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsePostMethod() {
    return $this->use_post_method;
  }

  /**
   * {@inheritdoc}
   */
  public function getDisableFollowLocation() {
    return $this->disable_follow_location;
  }

  /**
   * {@inheritdoc}
   */
  public function setLoginUrl($login_url) {
    $this->login_url = $login_url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsername($username) {
    $this->username = $username;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPassword($password) {
    $this->password = $password;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRetsVersion($rets_version) {
    $this->rets_version = $rets_version;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserAgent($user_agent) {
    $this->user_agent = $user_agent;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserAgentPassword($user_agent_password) {
    $this->user_agent_password = $user_agent_password;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHttpAuthentication($http_authentication) {
    $this->http_authentication = $http_authentication;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUsePostMethod($use_post_method) {
    $this->use_post_method = $use_post_method;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisableFollowLocation($disable_follow_location) {
    $this->disable_follow_location = $disable_follow_location;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addQuery($query_id, $label, $resource, $class, $query, $dmql, $format, $limit, $standardnames, $key_field, $entity, $select) {
    if (isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' already exists for the connection '{$this->id()}'");
    }
    if (preg_match('/[^a-z0-9_]+/', $query_id)) {
      throw new \InvalidArgumentException("The query ID '$query_id' must contain only lowercase letters, numbers, and underscores");
    }
    $this->queries[$query_id] = [
      'label' => $label,
    // todo: should be determined next weight value.
      'weight' => 0,
      'resource' => $resource,
      'class' => $class,
      'query' => $query,
      'dmql' => $dmql,
      'format' => $format,
      'limit' => $limit,
      'standardnames' => $standardnames,
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
      throw new \InvalidArgumentException("The query '$query_id' does not exist in the connection '{$this->id()}'");
    }
    $query = new RetsQuery(
      $this,
      $query_id,
      $this->queries[$query_id]['label'],
      $this->queries[$query_id]['weight'],
      $this->queries[$query_id]['resource'],
      $this->queries[$query_id]['class'],
      $this->queries[$query_id]['query'],
      $this->queries[$query_id]['dmql'],
      $this->queries[$query_id]['format'],
      $this->queries[$query_id]['limit'],
      $this->queries[$query_id]['standardnames'],
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
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['label'] = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryWeight($query_id, $weight) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['weight'] = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryResource($query_id, $resource) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['resource'] = $resource;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryClass($query_id, $class) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['class'] = $class;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryQuery($query_id, $query) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['query'] = $query;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryDmql($query_id, $dmql) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['dmql'] = $dmql;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryFormat($query_id, $format) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['format'] = $format;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryLimit($query_id, $limit) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['limit'] = $limit;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryStandardNames($query_id, $standardnames) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['standardnames'] = $standardnames;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQuerySelect($query_id, $select) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['select'] = $select;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryKeyField($query_id, $key_field) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['key_field'] = $key_field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueryEntity($query_id, $entity) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }
    $this->queries[$query_id]['entity'] = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQuery($query_id) {
    if (!isset($this->queries[$query_id])) {
      throw new \InvalidArgumentException("The query '$query_id' does not exist in connection '{$this->id()}'");
    }

    unset($this->queries[$query_id]);

    return $this;
  }

}
