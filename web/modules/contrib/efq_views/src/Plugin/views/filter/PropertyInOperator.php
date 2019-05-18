<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\PropertyInOperator.
 */

namespace Drupal\efq_views\Plugin\views\filter;

/**
 * Handle matching of multiple options selectable via checkboxes.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_property_in_operator")
 */
class PropertyInOperator extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->query->propertyCondition($this->real_field, array_values($this->value), $this->operator);
  }

}
