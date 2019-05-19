<?php

namespace Drupal\switches_test\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;

/**
 * Provides a condition that always evaluates to true.
 *
 * @Condition(
 *   id = "switch_test_true_condition",
 *   label = @Translation("Always true condition")
 * )
 */
class SwitchTestTrueCondition extends ConditionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('Condition that always evaluates to true.');
  }

}
