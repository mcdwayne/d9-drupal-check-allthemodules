<?php

namespace Drupal\fraction\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;

/**
 * Filter handler for Fraction fields.
 *
 * Overrides query function to use a formula which divides the numerator
 * by the denominator.
 *
 * Overrides operator functions (op_between, op_simple, and op_regex).
 *   Alter the add_where() method call in each to use the formula.
 *   Note that op_empty() is not included because we are not setting 'allow empty'.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("fraction")
 */
class Fraction extends NumericFilter {

  /**
   * {@inheritdoc}
   */
  public function query() {

    // Ensure the main table for this field is included.
    $this->ensureMyTable();

    // Formula for calculating the final value, by dividing numerator by denominator.
    // These are added as additional fields in hook_field_views_data_alter().
    $formula = $this->tableAlias . '.' . $this->definition['additional fields']['numerator'] . ' / ' . $this->tableAlias . '.' . $this->definition['additional fields']['denominator'];

    // Perform the filter using the selected operator and the formula.
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($formula);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    if ($this->operator == 'between') {
      $expression = $field . ' BETWEEN :min AND :max';
      $this->query->addWhereExpression($this->options['group'], $expression, array(':min' => $this->value['min'], ':max' => $this->value['max']));
    }
    else {
      $expression = $field . ' <= :min OR ' . $field . ' >= :max';
      $this->query->addWhereExpression($this->options['group'], $expression, array(':min' => $this->value['min'], ':max' => $this->value['max']));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple($field) {
    $expression = $field . ' ' . $this->operator . ' :value';
    $this->query->addWhereExpression($this->options['group'], $expression, array(':value' => $this->value['value']));
  }

  /**
   * {@inheritdoc}
   */
  protected function opRegex($field) {
    $expression = $field . ' RLIKE :value';
    $this->query->addWhereExpression($this->options['group'], $expression, array(':value' => $this->value['value']));
  }
}
