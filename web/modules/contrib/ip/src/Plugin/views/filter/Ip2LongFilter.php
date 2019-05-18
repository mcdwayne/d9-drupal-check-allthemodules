<?php

/**
 * @file
 * Contains \Drupal\ip\Plugin\views\filter\Ip2LongFilter.
 */

namespace Drupal\ip\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;

// use Drupal\Core\Form\FormStateInterface;

/**
 * Filter to handle greater than/less than ip2long filters
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("ip2long")
 */
class Ip2LongFilter extends NumericFilter {
  
  function opBetween($field) {
    if ($this->operator == 'between') {
      $this->query->addWhere($this->options['group'], $field, array(ip2long($this->value['min']), ip2long($this->value['max'])), 'BETWEEN');
    }
    else {
      $this->query->addWhere($this->options['group'], db_or()->condition($field, ip2long($this->value['min']), '<=')->condition($field, ip2long($this->value['max']), '>='));
    }
  }

  function opSimple($field) {
    $this->query->addWhere($this->options['group'], $field, ip2long($this->value['value']), $this->operator);
  }

  function opEmpty($field) {
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->addWhere($this->options['group'], $field, NULL, $operator);
  }

  function op_regex($field) {
    $this->query->addWhere($this->options['group'], $field, ip2long($this->value['value']), 'RLIKE');
  }
}
