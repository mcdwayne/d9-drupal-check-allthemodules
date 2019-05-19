<?php

/**
 * contains \Drupal\wisski_salz\WisskiQueryBase
 */
namespace Drupal\wisski_salz\Query;
use Drupal\Core\Entity\Query\QueryBase;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\wisski_salz\EngineInterface;

abstract class WisskiQueryBase extends QueryBase implements QueryInterface, QueryAggregateInterface {

  protected $parent_engine;
  
  protected $query_column_type;
  
  const FIELD_QUERY = 1;
  const PATH_QUERY = 2;

  public function __construct(EntityTypeInterface $entity_type,$condition,array $namespaces,EngineInterface $parent_engine=NULL) {
#    dpm($parent_engine, "par");
    $namespaces = array_merge($namespaces,QueryBase::getNamespaces($this));
    parent::__construct($entity_type,$condition,$namespaces);
    $this->parent_engine = $parent_engine;
    $this->query_column_type = self::FIELD_QUERY;
  }
  
  public function getEngine() {
    return $this->parent_engine;
  }
  
  public function normalQuery() {
    $this->count = FALSE;
    return $this;
  }
  
  public function countQuery() {
    $this->count = TRUE;
    return $this;
  }
  
  public function setPathQuery() {
    $this->query_column_type = self::PATH_QUERY;
  }
  
  public function setFieldQuery() {
    $this->query_column_type = self::FIELD_QUERY;
  }
  
  public function isFieldQuery() {
    return $this->query_column_type === self::FIELD_QUERY;
  }
  
  public function isPathQuery() {
    return $this->query_column_type === self::PATH_QUERY;
  }
  

}