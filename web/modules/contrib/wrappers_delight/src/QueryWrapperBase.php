<?php

namespace Drupal\wrappers_delight;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Base class for bundle query wrapper classes.
 *
 * @package Drupal\wrappers_delight
 */
class QueryWrapperBase implements QueryInterface {

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $query;

  /**
   * BundleWrapperQuery constructor.
   *
   * @param string $entity_type
   */
  public function __construct($entity_type, $bundle = NULL) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_info */
    $entity_info = \Drupal::service('entity_type.manager')->getDefinition($entity_type);
    $this->query = \Drupal::service('entity_type.manager')->getStorage($entity_type)->getQuery();
    if (!is_null($bundle)) {
      $this->query->condition($entity_info->getKey('bundle'), $bundle);
    }
  }

  /**
   * @return \Drupal\Core\Entity\Query\QueryInterface
   */
  public function raw() {
    return $this->query;
  }

  /**
   * @param string|$field_name
   * @param mixed|null $value
   * @param string|null $operator
   * @param string|null $langcode
   * 
   * @return static
   */
  public function byCondition($field_name, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->query->condition($field_name, $value, $operator, $langcode);
    return $this;
  }

  /**
   * @param string $field_name
   * @param string|null $langcode
   *
   * @return $this
   */
  public function byExists($field_name, $langcode = NULL) {
    $this->query->exists($field_name, $langcode);
    return $this;
  }

  /**
   * @param string $field_name
   * @param string|null $langcode
   *
   * @return $this
   */
  public function byNotExists($field_name, $langcode = NULL) {
    $this->query->notExists($field_name, $langcode);
    return $this;
  }

  /**
   * @param string $field_name
   * @param string $direction
   * @param string|null $langcode
   * 
   * @return static
   */
  public function sortBy($field_name, $direction = 'ASC', $langcode = NULL) {
    $this->query->sort($field_name, $direction, $langcode);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getEntityTypeId() {
    return $this->query->getEntityTypeId();
  }

  /**
   * @inheritDoc
   */
  public function condition($field, $value = NULL, $operator = NULL, $langcode = NULL) {
    return $this->query->condition($field, $value, $operator, $langcode);
  }

  /**
   * @inheritDoc
   */
  public function exists($field, $langcode = NULL) {
    return $this->query->exists($field, $langcode);
  }

  /**
   * @inheritDoc
   */
  public function notExists($field, $langcode = NULL) {
    return $this->query->notExists($field, $langcode);
  }

  /**
   * @inheritDoc
   */
  public function pager($limit = 10, $element = NULL) {
    return $this->query->pager($limit, $element);
  }

  /**
   * @inheritDoc
   */
  public function range($start = NULL, $length = NULL) {
    return $this->query->range($start, $length);
  }

  /**
   * @inheritDoc
   */
  public function sort($field, $direction = 'ASC', $langcode = NULL) {
    return $this->query->sort($field, $direction, $langcode);
  }

  /**
   * @inheritDoc
   */
  public function count() {
    return $this->query->count();
  }

  /**
   * @inheritDoc
   */
  public function tableSort(&$headers) {
    return $this->query->tableSort($headers);
  }

  /**
   * @inheritDoc
   */
  public function accessCheck($access_check = TRUE) {
    return $this->query->accessCheck($access_check);
  }

  /**
   * @inheritDoc
   */
  public function execute() {
    return $this->query->execute();
  }
  
  /**
   * @inheritDoc
   */
  public function andConditionGroup() {
    return $this->query->andConditionGroup();
  }

  /**
   * @inheritDoc
   */
  public function orConditionGroup() {
    return $this->query->orConditionGroup();
  }

  /**
   * @inheritDoc
   */
  public function currentRevision() {
    return $this->query->currentRevision();
  }

  /**
   * @inheritDoc
   */
  public function allRevisions() {
    return $this->query->allRevisions();
  }

  /**
   * @inheritDoc
   */
  public function addTag($tag) {
    return $this->query->addTag($tag);
  }

  /**
   * @inheritDoc
   */
  public function hasTag($tag) {
    return $this->query->hasTag($tag);
  }

  /**
   * @inheritDoc
   */
  public function hasAllTags() {
    return call_user_func_array([$this->query, 'hasAllTags'], func_get_args());
  }

  /**
   * @inheritDoc
   */
  public function hasAnyTag() {
    return call_user_func_array([$this->query, 'hasAnyTag'], func_get_args());
  }

  /**
   * @inheritDoc
   */
  public function addMetaData($key, $object) {
    return $this->query->addMetaData($key, $object);
  }

  /**
   * @inheritDoc
   */
  public function getMetaData($key) {
    return $this->query->getMetaData($key);
  }


  /**
   * @inheritDoc
   */
  function __call($name, $arguments) {
    return call_user_func_array([$this->query, $name], $arguments);
  }
  
}
