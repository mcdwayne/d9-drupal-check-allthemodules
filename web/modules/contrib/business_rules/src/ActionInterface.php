<?php

namespace Drupal\business_rules;

use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Provides an interface for defining Action entities.
 */
interface ActionInterface extends ItemInterface {

  /**
   * Execute the action.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event that has triggered the action.
   *
   * @return array
   *   The render array to be showed on debug block.
   *
   * @throws \ReflectionException
   */
  public function execute(BusinessRulesEvent $event);

}
