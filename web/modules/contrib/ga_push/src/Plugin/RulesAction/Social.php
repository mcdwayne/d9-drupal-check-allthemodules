<?php

namespace Drupal\ga_push\Plugin\RulesAction;

/**
 * Provides a 'Ga Push Social' action.
 *
 * @RulesAction(
 *   id = "ga_push_social",
 *   label = @Translation("Ga push: social"),
 *   category = @Translation("GA Push"),
 *   context = {
 *     "method" = @ContextDefinition("string",
 *       label = @Translation("Method"),
 *       description = @Translation("Select the method. If none is selected default method will be used."),
 *       required = FALSE,
 *     ),
 *     "socialNetwork" = @ContextDefinition("string",
 *       label = @Translation("Social network"),
 *       description = @Translation("The network on which the action occurs (e.g. Facebook, Twitter)."),
 *       required = TRUE
 *     ),
 *     "socialAction" = @ContextDefinition("string",
 *       label = @Translation("Social action"),
 *       description = @Translation("The type of action that happens (e.g. Like, Send, Tweet)."),
 *       required = TRUE
 *     ),
 *     "socialTarget" = @ContextDefinition("string",
 *       label = @Translation("Social target"),
 *       description = @Translation("Specifies the target of a social interaction. This value is typically a URL but can be any text. (e.g. http://mycoolpage.com)."),
 *       required = TRUE
 *     ),
 *   },
 * )
 */
class Social extends Base {

  /**
   * Executes the action with the given context.
   */
  protected function doExecute() {
    ga_push_add_social([
      'socialNetwork' => $this->getContextValue('socialNetwork'),
      'socialAction' => $this->getContextValue('socialAction'),
      'socialTarget' => $this->getContextValue('socialTarget'),
    ], $this->getMethod());
  }

}
