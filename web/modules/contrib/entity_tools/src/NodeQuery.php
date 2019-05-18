<?php

namespace Drupal\entity_tools;

/**
 * Class NodeQuery.
 *
 * Syntactic sugar over the core EntityQuery for filter, sort and limit.
 *
 * @package Drupal\entity_tools
 */
class NodeQuery extends AbstractEntityQuery implements EntityQueryInterface {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct(EntityTools::ENTITY_NODE);
    $this->setPublished();
    $this->stickyFirst();
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->coreEntityQuery->condition('type', $type);
  }

  /**
   * {@inheritdoc}
   */
  public function setTypes(array $types) {
    $group = $this->coreEntityQuery->orConditionGroup();
    foreach ($types as $type) {
      if (is_string($type)) {
        $group->condition('type', $type);
      }
    }
    $this->coreEntityQuery->condition($group);
  }

  /**
   * Filters by published entities.
   */
  public function setPublished() {
    $this->coreEntityQuery->condition('status', 1);
  }

  /**
   * Filters by unpublished entities.
   */
  public function setUnpublished() {
    $this->coreEntityQuery->condition('status', 0);
  }

  /**
   * Filters by promoted to front page entities.
   */
  public function setPromoted() {
    $this->coreEntityQuery->condition('promoted', 1);
  }

  /**
   * Sorts by latest publication date.
   */
  public function latestFirst() {
    $this->coreEntityQuery->sort('created', 'DESC');
  }

  /**
   * Sorts by sticky.
   */
  public function stickyFirst() {
    $this->coreEntityQuery->sort('sticky', 'DESC');
  }

}
