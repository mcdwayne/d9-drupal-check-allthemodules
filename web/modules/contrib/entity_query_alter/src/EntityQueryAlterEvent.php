<?php

namespace Drupal\entity_query_alter;

use Symfony\Component\EventDispatcher\Event;

class EntityQueryAlterEvent extends Event {

  /**
   * The entity query.
   *
   * @var \Drupal\entity_query_alter\SqlQuery
   */
  protected $query;

  /**
   * @inheritDoc
   */
  public function __construct(SqlQuery $query) {
    $this->query = $query;
  }

  /**
   * @return \Drupal\Core\Entity\Query\Sql\Query
   */
  public function getQuery() {
    return $this->query;
  }

}
