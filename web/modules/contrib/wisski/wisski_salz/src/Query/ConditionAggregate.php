<?php

namespace Drupal\wisski_salz\Query;

use Drupal\Core\Entity\Query\ConditionAggregateBase;

class ConditionAggregate extends ConditionAggregateBase {

  /**
   * {@inheritdoc}
   */
  public function compile($conditionContainer) {

  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $function, $langcode = NULL) {
    return $this->condition($field, $function, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $function, $langcode = NULL) {
    return $this->condition($field, $function, NULL, 'IS NULL', $langcode);
  }
}