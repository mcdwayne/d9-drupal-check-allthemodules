<?php

/**
 * @file
 * Definition of Drupal\efq_views\Plugin\views\argument\EntityNumeric.
 */

namespace Drupal\efq_views\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Numeric argument handler for fields.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("efq_field_numeric")
 */
class EntityFieldNumeric extends EntityNumeric {

  /**
   * {@inheritdoc}
   */
  function query($group_by = false) {
    if (!empty($this->options['break_phrase'])) {
      static::views_break_phrase($this->argument, $this);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'and';
    }

    if (count($this->value) > 1 && $this->operator == 'or') {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $this->query->query->fieldCondition($this->definition['field_name'], $this->real_field, $this->value, $operator);
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '<>';
      foreach ($this->value as $value) {
        $this->query->query->fieldCondition($this->definition['field_name'], $this->real_field, $value, $operator);
      }
    }
  }

}
