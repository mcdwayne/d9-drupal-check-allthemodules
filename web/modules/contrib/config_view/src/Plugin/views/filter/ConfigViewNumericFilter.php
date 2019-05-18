<?php

namespace Drupal\config_view\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\NumericFilter;

/**
 * Filter handler for user roles.
 *
 * Operators have to be modified to the operators used by EntityFieldQuery
 * For Drupal 8.1.x those are allowed operations: 'IN', 'NOT IN','BETWEEN',
 * '=', '<>', '>', '>=', '<', '<=', 'STARTS_WITH', 'CONTAINS', 'ENDS_WITH'.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("config_view_numeric_filter")
 */
class ConfigViewNumericFilter extends NumericFilter {

  /**
   * EntityFiledQuery allows only following operations.
   */
  public function operators() {
    $operators = [
      '<' => [
        'title' => $this->t('Is less than'),
        'method' => 'opSimple',
        'short' => $this->t('<'),
        'values' => 1,
      ],
      '<=' => [
        'title' => $this->t('Is less than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('<='),
        'values' => 1,
      ],
      '=' => [
        'title' => $this->t('Is equal to'),
        'method' => 'opSimple',
        'short' => $this->t('='),
        'values' => 1,
      ],
      '!=' => [
        'title' => $this->t('Is not equal to'),
        'method' => 'opSimple',
        'short' => $this->t('!='),
        'values' => 1,
      ],
      '>=' => [
        'title' => $this->t('Is greater than or equal to'),
        'method' => 'opSimple',
        'short' => $this->t('>='),
        'values' => 1,
      ],
      '>' => [
        'title' => $this->t('Is greater than'),
        'method' => 'opSimple',
        'short' => $this->t('>'),
        'values' => 1,
      ],
      'BETWEEN' => [
        'title' => $this->t('Is between'),
        'method' => 'opBetween',
        'short' => $this->t('BETWEEN'),
        'values' => 2,
      ],
    ];

    return $operators;
  }

}
