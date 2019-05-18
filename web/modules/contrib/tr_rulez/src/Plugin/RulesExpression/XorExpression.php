<?php

namespace Drupal\tr_rulez\Plugin\RulesExpression;

use Drupal\rules\Engine\ConditionExpressionContainer;
use Drupal\rules\Engine\ExecutionStateInterface;

/**
 * Evaluates a group of conditions with a logical XOR.
 *
 * When there are more than two conditions, XOR behaves like binary addition,
 * modulo 2. Thus, if an even number of conditions evalute to TRUE, an XOR
 * of those conditions evalutes to FALSE. And if an odd number of conditions
 * evaluate to TRUE, then an XOR of those conditions also evaluates to TRUE.
 *
 * @RulesExpression(
 *   id = "rules_xor",
 *   label = @Translation("Condition set (XOR)"),
 *   form_class = "\Drupal\tr_rulez\Form\Expression\ConditionContainerForm"
 * )
 */
class XorExpression extends ConditionExpressionContainer {

  /**
   * {@inheritdoc}
   */
  public function evaluate(ExecutionStateInterface $state) {
    $result = FALSE;
    foreach ($this->conditions as $condition) {
      if ($condition->executeWithState($state)) {
        $result = !$result;
      }
    }
    // An empty XOR should return FALSE. Otherwise, if an odd number of
    // conditions evaluate to TRUE we return TRUE.
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function allowsMetadataAssertions() {
    // We cannot guarantee child expressions are executed, thus we cannot allow
    // metadata assertions.
    return FALSE;
  }

}
