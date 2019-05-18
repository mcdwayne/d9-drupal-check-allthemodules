<?php

namespace Drupal\business_rules;

use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Variable entities.
 */
interface VariableInterface extends ConfigEntityInterface, ItemInterface {

  /**
   * Evaluate the variable.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The dispatched event.
   *
   * @return \Drupal\business_rules\VariableObject|\Drupal\business_rules\VariablesSet
   *   The evaluated variables.
   *
   * @throws \ReflectionException
   */
  public function evaluate(BusinessRulesEvent $event);

}
