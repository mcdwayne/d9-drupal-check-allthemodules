<?php

namespace Drupal\message_thread\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date;

/**
 * Filter handler for the newer of last message / thread updated.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("message_last_updated")
 */
class StatisticsLastUpdated extends Date {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->thread_table = $this->query->ensureTable('message_thread_field_data', $this->relationship);

    $field = "GREATEST(" . $this->thread_table . ".created, " . $this->tableAlias . ".last_message_timestamp)";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

}
