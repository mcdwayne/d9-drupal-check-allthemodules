<?php

namespace Drupal\real_estate_rets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining RETS Connection entities.
 */
interface RetsConnectionInterface extends ConfigEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getLoginUrl();

  /**
   * {@inheritdoc}
   */
  public function getUsername();

  /**
   * {@inheritdoc}
   */
  public function getPassword();

  /**
   * {@inheritdoc}
   */
  public function getRetsVersion();

  /**
   * {@inheritdoc}
   */
  public function getUserAgent();

  /**
   * {@inheritdoc}
   */
  public function getUserAgentPassword();

  /**
   * {@inheritdoc}
   */
  public function getHttpAuthentication();

  /**
   * {@inheritdoc}
   */
  public function getUsePostMethod();

  /**
   * {@inheritdoc}
   */
  public function getDisableFollowLocation();

  /**
   * {@inheritdoc}
   */
  public function setLoginUrl($login_url);

  /**
   * {@inheritdoc}
   */
  public function setUsername($username);

  /**
   * {@inheritdoc}
   */
  public function setPassword($password);

  /**
   * {@inheritdoc}
   */
  public function setRetsVersion($rets_version);

  /**
   * {@inheritdoc}
   */
  public function setUserAgent($user_agent);

  /**
   * {@inheritdoc}
   */
  public function setUserAgentPassword($user_agent_password);

  /**
   * {@inheritdoc}
   */
  public function setHttpAuthentication($http_authentication);

  /**
   * {@inheritdoc}
   */
  public function setUsePostMethod($use_post_method);

  /**
   * {@inheritdoc}
   */
  public function setDisableFollowLocation($disable_follow_location);

  /**
   * {@inheritdoc}
   */
  public function addQuery($query_id, $label, $resource, $class, $query, $dmql, $format, $limit, $standardnames, $key_field, $entity, $select);

  /**
   * {@inheritdoc}
   */
  public function hasQuery($query_id);

  /**
   * {@inheritdoc}
   */
  public function getQueries($query_ids = NULL);

  /**
   * {@inheritdoc}
   */
  public function getQuery($query_id);

  /**
   * {@inheritdoc}
   */
  public function setQueryLabel($query_id, $label);

  /**
   * {@inheritdoc}
   */
  public function setQueryWeight($query_id, $weight);

  /**
   * {@inheritdoc}
   */
  public function setQueryResource($query_id, $resource);

  /**
   * {@inheritdoc}
   */
  public function setQueryClass($query_id, $class);

  /**
   * {@inheritdoc}
   */
  public function setQueryQuery($query_id, $query);

  /**
   * {@inheritdoc}
   */
  public function setQueryDmql($query_id, $dmql);

  /**
   * {@inheritdoc}
   */
  public function deleteQuery($query_id);

  /**
   * {@inheritdoc}
   */
  public function setQueryFormat($query_id, $format);

  /**
   * {@inheritdoc}
   */
  public function setQueryLimit($query_id, $limit);

  /**
   * {@inheritdoc}
   */
  public function setQueryStandardNames($query_id, $standardnames);

  /**
   * {@inheritdoc}
   */
  public function setQuerySelect($query_id, $select);

  /**
   * {@inheritdoc}
   */
  public function setQueryKeyField($query_id, $key_field);

  /**
   * {@inheritdoc}
   */
  public function setQueryEntity($query_id, $entity);

}
