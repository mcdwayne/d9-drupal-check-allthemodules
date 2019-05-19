<?php

namespace Drupal\wisski_adapter_yaml\Query;

use Drupal\wisski_salz\Query\WisskiQueryBase;
use Drupal\wisski_salz\Query\ConditionAggregate;
use Drupal\Core\Entity\EntityTypeInterface;

class Query extends WisskiQueryBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $ents = $this->getEngine()->loadMultiple();
    foreach($this->condition->conditions() as $condition) {
      $field = $condition['field'];
      $value = $condition['value'];
      $ents = array_filter($ents,function($ent) use ($field,$value) {return $ent[$field] === $value;});
    }
    if ($this->count)
      return count($ents);
    else
      return array_keys($ents);
  }

  /**
   * {@inheritdoc}
   */
  public function existsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->exists($field, $function, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExistsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->notExists($field, $function, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function conditionAggregateGroupFactory($conjunction = 'AND') {
    return new ConditionAggregate($conjunction, $this);
  }
}