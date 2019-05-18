<?php

namespace Drupal\contest\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the total entries for a contest.
 *
 * @ViewsField("contest_total_entries")
 */
class ContestTotalEntries extends FieldPluginBase {

  /**
   * Get the total contest entries.
   *
   * @param Drupal\views\ResultRow $values
   *   A views result row.
   *
   * @return int
   *   The number of entries into the contest.
   */
  public function render(ResultRow $values) {
    return \Drupal::entityManager()->getStorage('contest')->getEntryCount($values->_entity);
  }

}
