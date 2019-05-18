<?php

namespace Drupal\message_thread\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Sort handler to sort by last message name which might be in 2 fields.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("message_ces_last_message_name")
 */
class StatisticsLastMessageName extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $definition = [
      'table' => 'users_field_data',
      'field' => 'uid',
      'left_table' => 'message_thread_statistics',
      'left_field' => 'last_message_uid',
    ];
    $join = \Drupal::service('plugin.manager.views.join')->createInstance('standard', $definition);

    // @todo this might be safer if we had an ensure_relationship rather than guessing
    // the table alias.
    // Though if we did that we'd be guessing the relationship name
    // so that doesn't matter that much.
    $this->user_table = $this->query->ensureTable('ces_users', $this->relationship, $join);
    $this->user_field = $this->query->addField($this->user_table, 'name');

    // Add the field.
    $this->query->addOrderBy(NULL, "LOWER(COALESCE($this->user_table.name, $this->tableAlias.$this->field))", $this->options['order'], $this->tableAlias . '_' . $this->field);
  }

}
