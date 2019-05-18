<?php

namespace Drupal\external_entities\Entity\Query\External;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Entity\Query\QueryException;

/**
 * Factory class creating entity query objects for the external backend.
 *
 * @see \Drupal\external_entities\Entity\Query\External\Query
 */
class QueryFactory implements QueryFactoryInterface {

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * Stores the entity manager used by the query.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a QueryFactory object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->namespaces = QueryBase::getNamespaces($this);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    if ($conjunction == 'OR') {
      throw new QueryException("External entity queries do not support OR conditions.");
    }
    $class = QueryBase::getClass($this->namespaces, 'Query');
    return new $class($entity_type, $conjunction, $this->namespaces, $this->entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    throw new QueryException("External entity queries do not support aggregate queries.");
  }

}
