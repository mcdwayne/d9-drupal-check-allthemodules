<?php

namespace Drupal\uc_order\Plugin\views\sort;

use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Simple sort on table.fieldname * table.qty.
 *
 * This sort handler is appropriate for any numeric formula that ends up
 * in the query with an alias like "table_field".
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("uc_order_total")
 */
class Total extends SortPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensure_my_table();
    // Add the field.
    $this->query->add_orderby(NULL, NULL, $this->options['order'], $this->table . '_' . $this->field);
  }

}
