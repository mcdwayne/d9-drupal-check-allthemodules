<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\PropertyBoolean.
 */

namespace Drupal\efq_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;

/**
 * Filter handler for boolean properties.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_property_boolean")
 */
class PropertyBoolean extends BooleanOperator {

  /**
   * {@inheritdoc}
   */
  function query() {
    if (empty($this->value)) {
      $this->query->query->propertyCondition($this->real_field, 0, "=");
    }
    else {
      $this->query->query->propertyCondition($this->real_field, 0, "<>");
    }
  }

}
