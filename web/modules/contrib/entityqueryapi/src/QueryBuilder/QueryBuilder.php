<?php

namespace Drupal\entityqueryapi\QueryBuilder;

use Drupal\Core\Entity\EntityTypeInterface;

class QueryBuilder implements QueryBuilderInterface {

  /**
   * The entity type object that should be used for the query.
   */
  protected $entityType;

  /**
   * The options to build with which to build a query.
   */
  protected $options = [];

  /**
   * A QueryFactory.
   */
  protected $queryFactory;

  /**
   * Contructs a new QueryBuilder instance.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An instance of a QueryFactory.
   */
  public function __construct($query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function newQuery(EntityTypeInterface $entity_type, array $options) {
    $this->entityType = $entity_type;

    $this->buildTree($options);

    $query = $this->queryFactory->get($this->entityType->id())->accessCheck(TRUE);
    
    // This applies each option from the option tree to the query before
    // returning it.
    $applied_query = array_reduce($this->options, function ($query, $option) {
      $query = $option->apply($query);
      return $query;
    }, $query);

    // Returns the basic query if no options were applied.
    return $applied_query ? $applied_query : $query;
  }

  /**
   * Builds a tree of QueryOptions.
   *
   * @param \Drupal\entityqueryapi\QueryBuilder\QueryOptionInterface[] $options
   *   An array of QueryOptions.
   */
  protected function buildTree(array $options) {
    $remaining = $options;
    while (!empty($remaining)) {
      $insert = array_pop($remaining);
      if (method_exists($insert, 'parentId') && $parent_id = $insert->parentId()) {
        if (!$this->insert($parent_id, $insert)) {
          array_unshift($remaining, $insert);
        }
      }
      else {
        $this->options[$insert->id()] = $insert;
      }
    }
  }

  /**
   * Inserts a QueryOption into the appropriate child QueryOption.
   *
   * @param string $target_id
   *   Unique ID of the intended QueryOption parent.
   * @param \Drupal\entityqueryapi\QueryBuilder\QueryOptionInterface $option
   *   The QueryOption to insert.
   *
   * @return bool
   *  Whether the option could be inserted or not.
   */
  protected function insert($target_id, QueryOptionInterface $option) {
    if (!empty($this->options)) {
      $find_target_child = function ($child, $option) use ($target_id) {
        if ($child) return $child;
        if ($option->id() == $target_id) return $option->id();
        if (method_exists($option, 'hasChild') && $option->hasChild($target_id)) {
          return $option->id();
        }
        return FALSE;
      };

      if ($appropriate_child = array_reduce($this->options, $find_target_child, NULL)) {
        return $this->options[$appropriate_child]->insert($target_id, $option);
      }
    }

    return FALSE;
  }

}
