<?php

/**
 * @file
 * Definition of Drupal\efq_views\Plugin\views\argument\EntityInteger.
 */

namespace Drupal\efq_views\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Integer argument handler for entity keys.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("efq_entity_integer")
 */
class EntityInteger extends Numeric {

  /**
   * {@inheritdoc}
   */
  function query() {
    if (!empty($this->options['break_phrase'])) {
      views_break_phrase($this->argument, $this);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'and';
    }

    if (count($this->value) > 1 && $this->operator == 'or') {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $this->query->query->entityCondition($this->real_field, array_map('intval', $this->value), $operator);
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '<>';
      foreach ($this->value as $value) {
        $this->query->query->entityCondition($this->real_field, (int) $value, $operator);
      }
    }
  }

}
