<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\Entity\Variable;
use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Defines an interface for Business rules variable plugins.
 */
interface BusinessRulesVariablePluginInterface extends BusinessRulesItemPluginInterface {

  /**
   * Evaluate the variable.
   *
   * @param \Drupal\business_rules\Entity\Variable $variable
   *   The variable to be evaluated.
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The dispatched event.
   *
   * @return \Drupal\business_rules\VariableObject|\Drupal\business_rules\VariablesSet
   *   The evaluated variables.
   */
  public function evaluate(Variable $variable, BusinessRulesEvent $event);

  /**
   * Change the variable details box.
   *
   * Give a chance to each variable plugin to change the variable details row on
   * Available Variables Box.
   *
   * @param \Drupal\business_rules\Entity\Variable $variable
   *   The variable.
   * @param array $row
   *   The row which contains the variable.
   */
  public function changeDetails(Variable $variable, array &$row);

}
