<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\EntityInteger.
 */

namespace Drupal\efq_views\Plugin\views\filter;

/**
 * Integer filter for entity keys.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_entity_integer")
 */
class EntityInteger extends Numeric {

  /**
   * {@inheritdoc}
   */
  function opSimple($name) {
    $this->query->query->entityCondition($name, (int) $this->value['value'], $this->operator);
  }

  /**
   * {@inheritdoc}
   */
  function opBetween($name) {
    $this->query->query->entityCondition($name, (int) array($this->value['min'], (int) $this->value['max']), "BETWEEN");
  }

}
