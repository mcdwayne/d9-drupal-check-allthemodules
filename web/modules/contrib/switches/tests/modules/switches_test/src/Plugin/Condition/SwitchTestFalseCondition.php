<?php

namespace Drupal\switches_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a condition that always evaluates to false.
 *
 * @Condition(
 *   id = "switch_test_false_condition",
 *   label = @Translation("Always false condition")
 * )
 */
class SwitchTestFalseCondition extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Condition that always evaluates to false.');
  }

}
