<?php

namespace Drupal\message_thread\Plugin\views\field;

use Drupal\views\Plugin\views\field\Date;

/**
 * Field handler to display the newer of last message / node updated.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("message_last_updated")
 */
class StatisticsLastUpdated extends Date {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->thread_table = $this->query->ensureTable('message_thread_field_data', $this->relationship);
    $this->field_alias = $this->query->addField(NULL, "GREATEST(" . $this->thread_table . ".created, " . $this->tableAlias . ".last_messaget_timestamp)", $this->tableAlias . '_' . $this->field);
  }

}
