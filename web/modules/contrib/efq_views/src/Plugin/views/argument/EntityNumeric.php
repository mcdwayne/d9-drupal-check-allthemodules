<?php

/**
 * @file
 * Definition of Drupal\efq_views\Plugin\views\argument\EntityNumeric.
 */

namespace Drupal\efq_views\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Numeric argument handler for entity properties.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("efq_entity_numeric")
 */
class EntityNumeric extends Numeric {

  /**
   * {@inheritdoc}
   */
  function query($group_by = false) {
    if (!empty($this->options['break_phrase'])) {
      views_break_phrase($this->argument, $this);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'and';
    }

    if (count($this->value) > 1 && $this->operator == 'or') {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $this->query->query->propertyCondition($this->real_field, $this->value, $operator);
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '!=';
      foreach ($this->value as $value) {
        $this->query->query->propertyCondition($this->real_field, $value, $operator);
      }
    }
  }

}
