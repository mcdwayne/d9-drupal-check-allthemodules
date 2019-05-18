<?php

namespace Drupal\entity_tools;

/**
 * Class BlockContentQuery.
 *
 * Syntactic sugar over the core EntityQuery for filter, sort and limit.
 *
 * @package Drupal\entity_tools
 */
class BlockContentQuery extends AbstractEntityQuery implements EntityQueryInterface {

  /**
   * Constructor.
   */
  public function __construct() {
    parent::__construct(EntityTools::ENTITY_USER);
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    // @todo implement
  }

  /**
   * {@inheritdoc}
   */
  public function setTypes(array $types) {
    // @todo implement
  }

}
