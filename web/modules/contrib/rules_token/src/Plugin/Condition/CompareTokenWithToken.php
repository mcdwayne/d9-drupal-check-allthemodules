<?php

namespace Drupal\rules_token\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Compare Token with Token' condition.
 *
 * @Condition(
 *   id = "rules_token_compare_token_with_token",
 *   label = @Translation("Compare Token with Token"),
 *   category = @Translation("Data"),
 *   context = {
 *     "token_1" = @ContextDefinition("string",
 *        label = @Translation("Token 1"),
 *        description = @Translation("The token 1 to be compared with the token 2."),
 *        assignment_restriction = "input",
 *     ),
 *     "token_entity_1" = @ContextDefinition("entity",
 *        label = @Translation("Entity of Token 1"),
 *        description = @Translation("Select from the selector the entity used in token 1. Or if you use global tokens like [date:short] then keep this field empty."),
 *        required = FALSE
 *     ),
 *     "operation" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The comparison operator. Valid values are == (default), <, >, CONTAINS (for strings or arrays) and IN (for arrays or lists)."),
 *       default_value = "==",
 *     ),
 *     "token_2" = @ContextDefinition("string",
 *        label = @Translation("Token 2"),
 *        description = @Translation("The token 2 to be compared with the token 1."),
 *        assignment_restriction = "input",
 *     ),
 *     "token_entity_2" = @ContextDefinition("entity",
 *        label = @Translation("Entity of Token 2"),
 *        description = @Translation("Select from the selector the entity used in token 2. Or if you use global tokens like [date:short] then keep this field empty."),
 *        required = FALSE
 *     )
 *   }
 * )
 */
class CompareTokenWithToken extends RulesConditionBase {

  /**
   * Get values of two tokens and compare it with each other.
   *
   * @param string $token_1
   *   The token to be compared against $token_2.
   * @param mixed $token_entity_1
   *   The entity from the context used in token 1.
   * @param string $operation
   *   Data comparison operation. Typically one of:
   *     - "=="
   *     - "<"
   *     - ">"
   *     - "contains" (for strings or arrays)
   *     - "IN" (for arrays or lists).
   * @param string $token_2
   *   The token to be compared against $token_1.
   * @param mixed $token_entity_2
   *   The entity from the context used in token 2.
   *
   * @return bool
   *   The evaluation of the condition.
   */
  protected function doEvaluate($token_1, $token_entity_1, $operation, $token_2, $token_entity_2) {
    // Set flag for removing token from the final text if no replacement value can be generated.
    // For, instance, if a node body is empty then token [node:body] will return '[node:body]' string.
    // Setting 'clear' to TRUE prevents such behaviour.
    $token_options = ['clear' => TRUE];
    // Get the value of the token 1.
    if ($token_1 && $token_entity_1) {
      // Extract entity name from a token, for instance if token is [node:created] then entity name will be 'node'.
      $entity_name = mb_substr($token_1, 1, strpos($token_1, ':') - 1);
      $token_data = [$entity_name => $token_entity_1];
      $value_1 = \Drupal::token()->replace($token_1, $token_data, $token_options);
    }
    elseif ($token_1) {
      $value_1 = \Drupal::token()->replace($token_1, [], $token_options);
    }

    // Get the value of the token 2.
    if ($token_2 && $token_entity_2) {
      // Extract entity name from a token, for instance if token is [node:created] then entity name will be 'node'.
      $entity_name = mb_substr($token_2, 1, strpos($token_2, ':') - 1);
      $token_data = [$entity_name => $token_entity_2];
      $value_2 = \Drupal::token()->replace($token_2, $token_data, $token_options);
    }
    elseif ($token_2) {
      $value_2 = \Drupal::token()->replace($token_2, [], $token_options);
    }

    // The following code is based on the code from the 'DataComparison' action of 'Rules' module.
    $operation = $operation ? strtolower($operation) : '==';
    switch ($operation) {
      case '<':
        return $value_1 < $value_2;

      case '>':
        return $value_1 > $value_2;

      case 'contains':
        return is_string($value_1) && strpos($value_1, $value_2) !== FALSE || is_array($value_1) && in_array($value_2, $value_1);

      case 'in':
        return is_array($value_2) && in_array($value_1, $value_2);

      case '==':
      default:
        // In case both values evaluate to FALSE, further differentiate between
        // NULL values and values evaluating to FALSE.
        if (!$value_1 && !$value_2) {
          return (isset($value_1) && isset($value_2)) || (!isset($value_1) && !isset($value_2));
        }
        return $value_1 == $value_2;
    }
  }

}
