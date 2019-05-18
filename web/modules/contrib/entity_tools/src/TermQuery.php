<?php

namespace Drupal\entity_tools;

/**
 * Class TermQuery.
 *
 * Syntactic sugar over the core EntityQuery for filter, sort and limit.
 *
 * @package Drupal\entity_tools
 */
class TermQuery extends AbstractEntityQuery implements EntityQueryInterface {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct(EntityTools::ENTITY_TERM);
    // @todo review order by weight by default
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->coreEntityQuery->condition('vid', $type);
  }

  /**
   * {@inheritdoc}
   */
  public function setTypes(array $types) {
    $group = $this->coreEntityQuery->orConditionGroup();
    foreach ($types as $type) {
      if (is_string($type)) {
        $group->condition('vid', $type);
      }
    }
    $this->coreEntityQuery->condition($group);
  }

}
