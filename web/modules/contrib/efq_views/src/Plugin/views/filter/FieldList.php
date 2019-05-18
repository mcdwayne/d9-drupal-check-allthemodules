<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\FieldList.
 */

namespace Drupal\efq_views\Plugin\views\filter;


/**
 * Filter handler which uses list-fields as options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_field_list")
 */
class FieldList extends FieldInOperator {

  /**
   * {@inheritdoc}
   */
  function getValueOptions() {
    $field = field_info_field($this->definition['field_name']);
    $this->valueOptions = list_allowed_values($field);
  }

}
