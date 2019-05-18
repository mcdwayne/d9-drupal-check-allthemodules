<?php

namespace Drupal\affected_by_promotion;

interface SupportsAffectedEntitiesQueryInterface {

  /**
   * Gets a query for all the entities that are affected by this promotion.
   *
   * One would have to do ranges and additional limitations by oneself.
   *
   * @return \Drupal\Core\Database\Query\Query
   *   A query, possible to execute.
   */
  public function getAffectedEntitiesQuery($entity_type_id);

}
