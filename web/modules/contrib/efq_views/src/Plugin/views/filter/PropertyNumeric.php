<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\PropertyNumeric.
 */

namespace Drupal\efq_views\Plugin\views\filter;

/**
 * Filter handler for numeric properties.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_property_numeric")
 */
class PropertyNumeric extends Numeric {

  /**
   * {@inheritdoc}
   */
  protected function opSimple($column) {
    $this->query->query->propertyCondition($column, $this->value['value'], $this->operator);
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($column) {
    $this->query->query->propertyCondition($column, array($this->value['min'], $this->value['max']), "BETWEEN");
  }

}
