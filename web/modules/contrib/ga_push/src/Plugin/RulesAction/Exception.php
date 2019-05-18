<?php

namespace Drupal\ga_push\Plugin\RulesAction;

/**
 * Provides a 'Ga Push Exception' action.
 *
 * @RulesAction(
 *   id = "ga_push_exception",
 *   label = @Translation("Ga push: exception"),
 *   category = @Translation("GA Push"),
 *   context = {
 *     "method" = @ContextDefinition("string",
 *       label = @Translation("Method"),
 *       description = @Translation("Select the method. If none is selected default method will be used."),
 *       required = FALSE,
 *     ),
 *     "exDescription" = @ContextDefinition("string",
 *       label = @Translation("Description"),
 *       description = @Translation("A description of the exception."),
 *       required = FALSE
 *     ),
 *     "exFatal" = @ContextDefinition("boolean",
 *       label = @Translation("Is Fatal?"),
 *       description = @Translation("Indicates whether the exception was fatal. true indicates fatal."),
 *     ),
 *   },
 * )
 */
class Exception extends Base {

  /**
   * Executes the action with the given context.
   */
  protected function doExecute() {
    ga_push_add_exception(
      [
        'exDescription' => $this->getContextValue('exDescription'),
        'exFatal' => $this->getContextValue('exFatal'),
      ],
      $this->getMethod()
    );
  }

}
