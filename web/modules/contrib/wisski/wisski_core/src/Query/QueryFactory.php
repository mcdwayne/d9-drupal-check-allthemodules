<?php

namespace Drupal\wisski_core\Query;

use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryFactoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;

use Drupal\wisski_salz\Query\WisskiQueryDelegator;

class QueryFactory implements QueryFactoryInterface {

  /**
   * The namespace of this class, the parent class etc.
   *
   * @var array
   */
  protected $namespaces;

  /**
   * Constructs a QueryFactory object.
   */
  public function __construct() {
    $this->namespaces = QueryBase::getNamespaces($this);
  }

  /**
   * {@inheritdoc}
   * returns a WisskiQueryDelegator Object, that can dispatch the conditions to the respective adapter query objects
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    
    return new WisskiQueryDelegator($entity_type,$conjunction,$this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
  
    return new WisskiQueryDelegator($entity_type,$conjuction,$this->namespaces);
  }

}

