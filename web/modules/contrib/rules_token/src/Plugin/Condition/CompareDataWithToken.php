<?php

namespace Drupal\rules_token\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Compare Data with Token' condition.
 *
 * @Condition(
 *   id = "rules_token_compare_data_with_token",
 *   label = @Translation("Compare Data with Token"),
 *   category = @Translation("Data"),
 *   context = {
 *     "data" = @ContextDefinition("any",
 *       label = @Translation("Data"),
 *       description = @Translation("The data to be compared with the token.")
 *     ),
 *     "operation" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The comparison operator. Valid values are == (default), <, >, CONTAINS (for strings or arrays) and IN (for arrays or lists)."),
 *       default_value = "==",
 *     ),
 *     "token" = @ContextDefinition("string",
 *        label = @Translation("Token"),
 *        description = @Translation("The token to be compared with the data."),
 *        assignment_restriction = "input",
 *     ),
 *     "token_entity" = @ContextDefinition("entity",
 *        label = @Translation("Entity of Token"),
 *        description = @Translation("Select from the selector the entity used in token. Or if you use global tokens like [date:short] then keep this field empty."),
 *        required = FALSE
 *     )
 *   }
 * )
 */
class CompareDataWithToken extends RulesConditionBase {

  /**
   * Get values of two tokens and compare it with each other.
   *
   * @param mixed $data
   *   The data to be compared with $token.
   * @param string $operation
   *   Data comparison operation. Typically one of:
   *     - "=="
   *     - "<"
   *     - ">"
   *     - "contains" (for strings or arrays)
   *     - "IN" (for arrays or lists).
   * @param string $token
   *   The token to be compared with $data.
   * @param mixed $token_entity
   *   The entity from the context used in token.
   *
   * @return bool
   *   The evaluation of the condition.
   */
  protected function doEvaluate($data, $operation, $token, $token_entity) {
    // Set flag for removing token from the final text if no replacement value can be generated.
    // For, instance, if a node body is empty then token [node:body] will return '[node:body]' string.
    // Setting 'clear' to TRUE prevents such behaviour.
    $token_options = ['clear' => TRUE];
    // Get the value of the token 1.
    if ($token && $token_entity) {
      // Extract entity name from a token, for instance if token is [node:created] then entity name will be 'node'.
      $entity_name = mb_substr($token, 1, strpos($token, ':') - 1);
      $token_data = [$entity_name => $token_entity];
      $value = \Drupal::token()->replace($token, $token_data, $token_options);
    }
    elseif ($token) {
      $value = \Drupal::token()->replace($token, [], $token_options);
    }

    // The following code is based on the code from the 'DataComparison' action of 'Rules' module.
    $operation = $operation ? strtolower($operation) : '==';
    switch ($operation) {
      case '<':
        return $data < $value;

      case '>':
        return $data > $value;

      case 'contains':
        return is_string($data) && strpos($data, $value) !== FALSE || is_array($data) && in_array($value, $data);

      case 'in':
        return is_array($value) && in_array($data, $value);

      case '==':
      default:
        // In case both values evaluate to FALSE, further differentiate between
        // NULL values and values evaluating to FALSE.
        if (!$data && !$value) {
          return (isset($data) && isset($value)) || (!isset($data) && !isset($value));
        }
        return $data == $value;
    }
  }

}
