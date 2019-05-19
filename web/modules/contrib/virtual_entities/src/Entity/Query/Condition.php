<?php

namespace Drupal\virtual_entities\Entity\Query;

use Drupal\Core\Entity\Query\ConditionBase;
use Drupal\Core\Entity\Query\ConditionInterface;

/**
 * Class Condition.
 *
 * @package Drupal\virtual_entities\Entity\Query
 */
class Condition extends ConditionBase {

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

  /**
   * {@inheritdoc}
   */
  public function compile($query) {
    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        $query_condition = new static('AND', $this->query);
        $query_condition->compile($query);
      }
      else {
        $query->setParameter($condition['field'], $condition['value']);
      }
    }
  }

}
