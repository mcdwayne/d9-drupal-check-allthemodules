<?php

namespace Drupal\rules_token\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides an 'Get token value' action.
 *
 * @RulesAction(
 *   id = "rules_token_get_token_value",
 *   label = @Translation("Get token value"),
 *   category = @Translation("Data"),
 *   context = {
 *     "token" = @ContextDefinition("string",
 *       label = @Translation("Token"),
 *       assignment_restriction = "input"
 *     ),
 *     "token_entity" = @ContextDefinition("any",
 *       label = @Translation("Entity of Token"),
 *       description = @Translation("Select from the selector the entity used in token. Or if you use global tokens like [date:short] then keep this field empty."),
 *        required = FALSE
 *     )
 *   },
 *   provides = {
 *     "token_value" = @ContextDefinition("any",
 *        label = @Translation("Token value")
 *      )
 *    }
 * )
 */
class GetTokenValue extends RulesActionBase {

  /**
   * Getting a token value and provide it into context.
   *
   * @param string $token
   *   The token.
   * @param mixed $token_entity
   *   The entity from the context used in token.
   */
  protected function doExecute($token, $token_entity) {
    // Set flag for removing token from the final text if no replacement value can be generated.
    // For, instance, if a node body is empty then token [node:body] will return '[node:body]' string.
    // Setting 'clear' to TRUE prevents such behaviour.
    $token_options = ['clear' => TRUE];
    // Get the value of the token.
    if ($token && $token_entity) {
      // Extract entity name from a token, for instance if token is [node:created] then entity name will be 'node'.
      $entity_name = mb_substr($token, 1, strpos($token, ':') - 1);
      $token_data = [$entity_name => $token_entity];
      $value = \Drupal::token()->replace($token, $token_data, $token_options);
    }
    elseif ($token) {
      $value = \Drupal::token()->replace($token, [], $token_options);
    }

    $this->setProvidedValue('token_value', $value);
  }

}
