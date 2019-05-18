<?php

namespace Drupal\contest\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the end date for a contest.
 *
 * @ViewsField("contest_end_date")
 */
class ContestEndDate extends FieldPluginBase {

  /**
   * Get the contest end date.
   *
   * @param Drupal\views\ResultRow $values
   *   A views result row.
   *
   * @return string
   *   The contest end date.
   */
  public function render(ResultRow $values) {
    $date = \Drupal::entityManager()->getStorage('contest')->getEndDate($values->_entity);
    return $date ? \Drupal::service('date.formatter')->format($date, 'custom', 'F j Y') : '&mdash';
  }

}
