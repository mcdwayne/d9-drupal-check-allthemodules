<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Base class containing useful functions for some Order conditions.
 */
abstract class OrderConditionBase extends RulesConditionBase {

  /**
   * Operator options callback.
   *
   * @return string[]
   *   An array of logic operations for multiple role matching.
   */
  public function comparisonOptions() {
    return [
      'less' => $this->t('Total is less than specified value.'),
      'less_equal' => $this->t('Total is less than or equal to specified value.'),
      'equal' => $this->t('Total is equal to specified value.'),
      'greater_equal' => $this->t('Total is greater than or equal to specified value.'),
      'greater' => $this->t('Total is greater than specified value.'),
    ];
  }

  /**
   * Value comparison.
   *
   * @param float $source
   *   The source value.
   * @param string $operator
   *   The comparison operator.
   * @param float $target
   *   The target value.
   *
   * @return bool
   *   Whether the comparison meets the specified conditions.
   */
  public function compareComparisonOptions($source, $operator, $target) {
    switch ($operator) {
      case 'less':
        return $source < $target;

      case 'less_equal':
        return $source <= $target;

      case 'equal':
        return $source == $target;

      case 'greater_equal':
        return $source >= $target;

      case 'greater':
        return $source > $target;
    }
  }

}
