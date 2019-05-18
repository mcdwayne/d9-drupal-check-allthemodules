<?php

namespace Drupal\entity_tools;

/**
 * Class GenericQuery.
 *
 * Provides default implementation for not implemented yet
 * _ID_Query over the core EntityQuery for filter, sort and limit.
 *
 * @package Drupal\entity_tools
 */
class GenericQuery extends AbstractEntityQuery implements EntityQueryInterface {

  /**
   * Constructor.
   */
  public function __construct($entity) {
    parent::__construct($entity);
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

}
