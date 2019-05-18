<?php

namespace Drupal\contest\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the start date for a contest.
 *
 * @ViewsField("contest_start_date")
 */
class ContestStartDate extends FieldPluginBase {

  /**
   * Get the contest start date.
   *
   * @param Drupal\views\ResultRow $values
   *   A views result row.
   *
   * @return string
   *   The contest start date.
   */
  public function render(ResultRow $values) {
    $date = \Drupal::entityManager()->getStorage('contest')->getStartDate($values->_entity);
    return $date ? \Drupal::service('date.formatter')->format($date, 'custom', 'F j Y') : '&mdash';
  }

}
