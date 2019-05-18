<?php

namespace Drupal\ga_push\Plugin\RulesAction;

/**
 * Provides a 'Ga Push Event' action.
 *
 * @RulesAction(
 *   id = "ga_push_event",
 *   label = @Translation("Ga push: event"),
 *   category = @Translation("GA Push"),
 *   context = {
 *     "method" = @ContextDefinition("string",
 *       label = @Translation("Method"),
 *       description = @Translation("Select the method. If none is selected default method will be used."),
 *       required = FALSE,
 *     ),
 *     "event" = @ContextDefinition("string",
 *       label = @Translation("Event"),
 *       description = @Translation("The event name for Data Layer."),
 *       required = FALSE,
 *     ),
 *     "category" = @ContextDefinition("string",
 *       label = @Translation("Category"),
 *       description = @Translation("The name you supply for the group of objects you want to track.")
 *     ),
 *     "action" = @ContextDefinition("string",
 *       label = @Translation("Action"),
 *       description = @Translation("A string that is uniquely paired with each category, and commonly used to define the type of user interaction for the web object.")
 *     ),
 *     "label" = @ContextDefinition("string",
 *       label = @Translation("Label"),
 *       description = @Translation("An optional string to provide additional dimensions to the event data."),
 *       required = FALSE
 *     ),
 *     "value" = @ContextDefinition("integer",
 *       label = @Translation("Value"),
 *       description = @Translation("An integer that you can use to provide numerical data about the user event."),
 *       required = FALSE
 *     ),
 *     "non-interaction" = @ContextDefinition("boolean",
 *       label = @Translation("Non interaction"),
 *       description = @Translation("A boolean that when set to true, indicates that the event hit will not be used in bounce-rate calculation."),
 *       default_value = FALSE,
 *       required = FALSE
 *     )
 *   },
 * )
 */
class Event extends Base {

  /**
   * Executes the action with the given context.
   */
  protected function doExecute() {
    $event = [
      'eventCategory'        => $this->getContextValue('category'),
      'eventAction'          => $this->getContextValue('action'),
      'eventLabel'           => $this->getContextValue('label'),
      'eventValue'           => $this->getContextValue('value'),
      'nonInteraction'       => $this->getContextValue('non-interaction'),
    ];

    // Retrieve selected method or the default one.
    $method = $this->getMethod();

    if ($this->isDatalayerMethod()) {
      $event['event'] = $this->getContextValue('event');
    }

    ga_push_add_event($event, $method);
  }

  /**
   * Check if selected method is datalayer.
   *
   * @return bool
   *   Is datalayer method?
   */
  public function isDatalayerMethod() {
    $method = $this->getContextValue('method');
    return $method == GA_PUSH_METHOD_DATALAYER_JS || $this->getDefaultMethod() == GA_PUSH_METHOD_DATALAYER_JS;
  }

}
