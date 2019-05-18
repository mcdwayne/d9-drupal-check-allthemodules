<?php

namespace Drupal\real_estate_openimmo\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining OpenImmo Source entities.
 */
interface OpenImmoInterface extends ConfigEntityInterface {

  /**
   * {@inheritdoc}
   */
  public function getFeedType();

  /**
   * {@inheritdoc}
   */
  public function setFeedType($feed_type);

  /**
   * {@inheritdoc}
   */
  public function getFeedConfig();

  /**
   * {@inheritdoc}
   */
  public function addFeedConfig(array $feed_config);

  /**
   * {@inheritdoc}
   */
  public function addQuery($query_id, $label, $key_field, $entity, $select);

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
