<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\FieldDate.
 */

namespace Drupal\efq_views\Plugin\views\filter;

/**
 * Filter handler for date fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_field_date")
 */
class FieldDate extends Date {

  /**
   * {@inheritdoc}
   */
  function opBetween($field) {
    $this->query->query->fieldCondition($this->definition['field_name'], $this->real_field, 'value', parent::opBetween($field), 'BETWEEN');
  }

  /**
   * {@inheritdoc}
   */
  function opSimple($field) {
    $this->query->query->fieldCondition($this->definition['field_name'], $this->real_field, parent::opSimple($field), $this->operator);
  }

}
