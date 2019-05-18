<?php

namespace Drupal\condition_plugins_commerce\Plugin;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides the base class for conditions.
 */
abstract class ConditionBase extends ConditionPluginBase {

  /**
   * Gets the comparison operators.
   *
   * @return array
   *   The comparison operators.
   */
  protected function getComparisonOperators() {
    return [
      '>' => $this->t('Greater than'),
      '>=' => $this->t('Greater than or equal to'),
      '<=' => $this->t('Less than or equal to'),
      '<' => $this->t('Less than'),
      '==' => $this->t('Equals'),
    ];
  }

}
