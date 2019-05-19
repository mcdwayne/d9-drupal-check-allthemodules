<?php

namespace Drupal\views_order_by_delta\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Defines a custom sort handler to sort items by the referenced field delta.
 *
 * @ViewsSort("order_by_delta")
 */
class OrderByDelta extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * Inject custom order by.
   */
  public function query() {
    // Before adding the order by, we make sure it is in a join. We can't just
    // use ensureTable() here, the handler does not have a table at all and
    // depends on the relationship, if the relationship is removed we don't
    // want to add a order by to a table that does not exists, because if we do
    // that, a join will be created and we will fall in the same problem that
    // we are trying to solve here.
    $has_table = FALSE;
    $table_name = str_replace('__views_order_by_delta', '', $this->realField);
    foreach ($this->query->tables as $table) {
      if (isset($table[$table_name])) {
        $has_table = TRUE;
        break;
      }
    }
    if ($has_table) {
      $this->query->addOrderBy($table_name, 'delta', $this->options['order']);
    }
  }

}
