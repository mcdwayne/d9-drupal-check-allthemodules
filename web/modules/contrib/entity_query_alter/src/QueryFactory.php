<?php

namespace Drupal\entity_query_alter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

class QueryFactory extends \Drupal\Core\Entity\Query\Sql\QueryFactory {

  /**
   * The decorated service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
  protected $decorated;

  /**
   * @inheritDoc
   */
  public function __construct(QueryFactoryInterface $queryFactory, Connection $connection) {
    $this->decorated = $queryFactory;
    $this->connection = $connection;
    $this->namespaces = ['Drupal\Core\Entity\Query\Sql'];
  }

  /**
   * @inheritDoc
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    return new SqlQuery($entity_type, $conjunction, $this->connection, $this->namespaces);
  }

}
