<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\VariablesSet;

/**
 * Defines an interface for Business rules Condition plugins.
 */
interface BusinessRulesConditionPluginInterface extends BusinessRulesItemPluginInterface {

  /**
   * Process the condition.
   *
   * @param \Drupal\business_rules\ConditionInterface $condition
   *   The configured condition.
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event that has triggered the condition.
   *
   * @return bool
   *   Boolean value that indicates if the condition is true.
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event);

  /**
   * Process the item the variables for it's values.
   *
   * @param mixed $content
   *   The item to be replaced by the variable value.
   * @param \Drupal\business_rules\VariablesSet $event_variables
   *   Array of Variables provided by the event.
   *
   * @return mixed
   *   The processed content, replacing the variables tokens for it's values.
   */
  public function processVariables($content, VariablesSet $event_variables);

}
