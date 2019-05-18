<?php

namespace Drupal\quick_code\Plugin\views\filter;

use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter handler for the effective data.
 *
 * @ViewsFilter("quick_code_effective")
 */
class Effective extends BooleanOperator {

  public function query() {
    $this->ensureMyTable();

    $today = new DrupalDateTime('now', DATETIME_STORAGE_TIMEZONE);
    $today = $today->format(DATETIME_DATE_STORAGE_FORMAT);
    $value = $this->tableAlias . '.effective_dates__value';
    $end_value = $this->tableAlias . '.effective_dates__end_value';
    if ($this->value) {
      $or1 = new Condition('OR');
      $or1->condition($value, NULL, 'IS NULL')
        ->condition($value, $today, '<=');
      $or2 = new Condition('OR');
      $or2->condition($end_value, NULL, 'IS NULL')
        ->condition($end_value, '')
        ->condition($end_value, $today, '>');
      $and = new Condition('AND');
      $and->condition($or1)
        ->condition($or2);
      $this->query->addWhere($this->options['group'], $and);
    }
    /*else {
      $or = new Condition('OR');
      $or->condition($value, $today, '>');

      $and = new Condition('AND');
      $and->condition($end_value, NULL, 'IS NOT NULL');
      $and->condition($end_value, '', '<>');
      $and->condition($end_value, $today, '<');

      $or->condition($and);
      $this->query->addWhere($this->options['group'], $or);
    }*/
  }

  public function getValueOptions() {
    $this->valueOptions = [1 => $this->t('Effective'), 0 => $this->t('All')];
  }

}
