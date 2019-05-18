<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\PropertyString.
 */

namespace Drupal\efq_views\Plugin\views\filter;

/**
 * Filter handler for textual properties.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_string")
 */
class PropertyString extends String {

  /**
   * {@inheritdoc}
   */
  protected function opSimple($column) {
    $this->query->query->propertyCondition($column, $this->value, $this->operator);
  }

}
