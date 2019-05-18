<?php

namespace Drupal\entityqueryapi\QueryBuilder;

use Drupal\Core\Entity\EntityTypeInterface;

interface QueryBuilderInterface {

  /**
   * Creates a new Entity Query.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which to create a query.
   * @param \Drupal\entityqueryapi\QueryBuilder\QueryOptionInterface[] $options
   *   A flat array of QueryOptions to apply to the query.
   */
  public function newQuery(EntityTypeInterface $entity_type, array $options);

}
