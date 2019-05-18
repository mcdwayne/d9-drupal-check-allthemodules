<?php

namespace Drupal\message_thread\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\Date;

/**
 * Sort handler for the newer of last message / entity updated.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("message_last_updated")
 */
class StatisticsLastUpdated extends Date {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->thread_table = $this->query->ensureTable('message_thread_field_data', $this->relationship);
    $this->field_alias = $this->query->addOrderBy(NULL, "GREATEST(" . $this->thread_table . ".created, " . $this->tableAlias . ".last_message_timestamp)", $this->options['order'], $this->tableAlias . '_' . $this->field);
  }

}
