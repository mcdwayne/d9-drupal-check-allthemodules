<?php

namespace Drupal\external_entities\Entity\Query\External;

use Drupal\Core\Entity\Query\ConditionBase;
use Drupal\Core\Entity\Query\ConditionInterface;

/**
 * Implements entity query conditions for SQL databases.
 */
class Condition extends ConditionBase {

  /**
   * The SQL entity query object this condition belongs to.
   *
   * @var \Drupal\Core\Entity\Query\Sql\Query
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function compile($query) {
    /* @var \Drupal\external_entities\Entity\Query\External\Query $query */
    // If this is not the top level condition group then the sql query is
    // added to the $conditionContainer object by this function itself. The
    // SQL query object is only necessary to pass to Query::addField() so it
    // can join tables as necessary. On the other hand, conditions need to be
    // added to the $conditionContainer object to keep grouping.
    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        $query_condition = new static('AND', $this->query);
        $query_condition->compile($query);
      }
      else {
        $query->setParameter($condition['field'], $condition['value'], $condition['operator']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NULL', $langcode);
  }

}
