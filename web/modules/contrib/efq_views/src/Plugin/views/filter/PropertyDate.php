<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\String.
 */

namespace Drupal\efq_views\Plugin\views\filter;


/**
 * Filter handler for date properties.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_property_date")
 */
class PropertyDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    $this->query->query->propertyCondition($field, parent::opBetween($field), 'BETWEEN');
  }

  /**
   * {@inheritdoc}
   */
  protected function  opSimple($field) {
    $this->query->query->propertyCondition($field, parent::opBetween($field), $this->operator);
  }

}
